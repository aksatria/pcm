<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Dev\BaseController;
use App\Models\Client;
use App\Models\Project;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientController extends BaseController
{
    /**
     * Ensure only HO can perform destructive actions (delete).
     */
    private function ensureHO(\Illuminate\Http\Request $request, string $message = 'Hanya HO yang boleh menghapus data.')
    {
        $user = auth()->user();
        $isHO = $user && method_exists($user, 'isHO') ? $user->isHO() : (($user->role ?? '') === 'ho');

        if ($isHO) {
            return null; // allowed
        }

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return redirect()->back()->with('error', $message);
    }


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Client::withCount('projects');
            
            // Search functionality
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->has('category') && $request->category != '') {
                $query->where('category', $request->category);
            }
            
            // Sort options
            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'oldest':
                        $query->oldest();
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'projects_high':
                        $query->orderBy('projects_count', 'desc');
                        break;
                    case 'projects_low':
                        $query->orderBy('projects_count', 'asc');
                        break;
                    default:
                        $query->latest();
                        break;
                }
            } else {
                $query->latest();
            }
            
            $clients = $query->paginate(12);

            return view('dev.clients.index', compact('clients'));

        } catch (\Exception $e) {
            Log::error('Client Index Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat data clients: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $categories = ['Corporate', 'Government', 'Individual'];
            return view('dev.clients.create', compact('categories'));

        } catch (\Exception $e) {
            Log::error('Client Create Form Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form client: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('ðŸŽ¯ CLIENT STORE START');
        
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'company' => 'nullable|string|max:255',
                'category' => 'required|string|in:Corporate,Government,Individual',
                'notes' => 'nullable|string'
            ], [
                'name.required' => 'Nama client wajib diisi',
                'email.email' => 'Format email tidak valid',
                'category.required' => 'Kategori wajib dipilih',
                'category.in' => 'Kategori yang dipilih tidak valid'
            ]);

            $client = Client::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'company' => $request->company,
                'category' => $request->category,
                'notes' => $request->notes,
            ]);

            AuditLogger::log('client.created', $client, null, $client->toArray(), [
                'client_name' => $client->name,
                'category' => $client->category,
            ]);

            Log::info('ðŸŽ‰ CLIENT CREATED:', [
                'id' => $client->id, 
                'name' => $client->name,
                'category' => $client->category
            ]);

            return redirect()->route('dev.clients.index')
                ->with('success', 'Client berhasil dibuat!');

        } catch (\Exception $e) {
            Log::error('âŒ CLIENT CREATE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $client = Client::findOrFail($id);
            
            // Load projects dengan data dasar
            $client->load(['projects' => function($query) {
                $query->latest();
            }]);

            // Hitung data untuk setiap project
            $client->projects->each(function($project) {
                $project->budget = $project->budget ?? 0;
            });

            $client->projects_count = $client->projects->count();
            $client->total_budget = $client->projects->sum('budget');

            return view('dev.clients.show', compact('client'));

        } catch (\Exception $e) {
            Log::error('Client Show Error:', [
                'client_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Client tidak ditemukan: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $client = Client::findOrFail($id);
            $categories = ['Corporate', 'Government', 'Individual'];
            
            return view('dev.clients.edit', compact('client', 'categories'));

        } catch (\Exception $e) {
            Log::error('Client Edit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit client: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Log::info('ðŸŽ¯ CLIENT UPDATE START');
        
        try {
            $client = Client::findOrFail($id);
            $before = $client->toArray();

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'company' => 'nullable|string|max:255',
                'category' => 'required|string|in:Corporate,Government,Individual',
                'notes' => 'nullable|string'
            ], [
                'name.required' => 'Nama client wajib diisi',
                'email.email' => 'Format email tidak valid',
                'category.required' => 'Kategori wajib dipilih',
                'category.in' => 'Kategori yang dipilih tidak valid'
            ]);

            $client->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'company' => $request->company,
                'category' => $request->category,
                'notes' => $request->notes,
            ]);

            AuditLogger::log('client.updated', $client, $before, $client->toArray(), [
                'client_name' => $client->name,
                'category' => $client->category,
            ]);

            Log::info('ðŸŽ‰ CLIENT UPDATED:', [
                'id' => $client->id, 
                'name' => $client->name,
                'category' => $client->category
            ]);

            return redirect()->route('dev.clients.index')
                ->with('success', 'Client berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('âŒ CLIENT UPDATE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
        /**
     * Remove the specified resource from storage.
     */
    public function destroy(\Illuminate\Http\Request $request, $id)
    {
        Log::info('CLIENT DESTROY START');
        
        try {
            $client = Client::findOrFail($id);
            $before = $client->toArray();
            $clientName = $client->name;
            
            // Check if client has projects
            if ($client->projects()->count() > 0) {
                return back()->with('error', 'Tidak dapat menghapus client yang masih memiliki project!');
            }

            $isHO = auth()->check() && auth()->user()->isHO();

            if ($isHO) {
                $client->delete();
                AuditLogger::log('client.deleted', $client, $before, null, [
                    'client_name' => $clientName,
                    'deleted_by' => auth()->user()->name ?? 'HO',
                ]);

                Log::info('CLIENT DELETED:', ['id' => $client->id, 'name' => $clientName]);

                return redirect()->route('dev.clients.index')
                    ->with('success', 'Client "' . $clientName . '" berhasil dihapus!');
            }

            if ($client->delete_status === 'pending') {
                return back()->with('error', 'Client ini sudah menunggu approval penghapusan.');
            }

            $client->update([
                'delete_status' => 'pending',
                'delete_requested_by' => auth()->check() ? auth()->user()->name : 'system',
                'delete_requested_at' => now(),
                'delete_reason' => $request->input('delete_reason'),
            ]);
            AuditLogger::log('client.delete_requested', $client, $before, $client->toArray(), [
                'client_name' => $clientName,
                'requested_by' => auth()->user()->name ?? 'system',
                'reason' => $request->input('delete_reason'),
            ]);


            return redirect()->route('dev.clients.index')
                ->with('success', 'Permintaan hapus client "' . $clientName . '" telah dikirim ke HO.');

        } catch (\Exception $e) {
            Log::error('CLIENT DELETE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function approveDelete(\Illuminate\Http\Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menyetujui penghapusan.');
        }

        $client = Client::findOrFail($id);
        $before = $client->toArray();
        if ($client->delete_status !== 'pending') {
            return back()->with('error', 'Client tidak dalam status menunggu penghapusan.');
        }

        $client->update([
            'delete_status' => 'approved',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
        ]);
        AuditLogger::log('client.delete_approved', $client, $before, $client->toArray(), [
                'client_name' => $client->name,
            'reviewed_by' => auth()->user()->name ?? 'HO',
        ]);


        $client->delete();

        return redirect()->route('dev.clients.index')->with('success', 'Penghapusan client disetujui.');
    }

    public function rejectDelete(\Illuminate\Http\Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menolak penghapusan.');
        }

        $client = Client::findOrFail($id);
        $before = $client->toArray();
        if ($client->delete_status !== 'pending') {
            return back()->with('error', 'Client tidak dalam status menunggu penghapusan.');
        }

        $client->update([
            'delete_status' => 'rejected',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
            'delete_review_note' => $request->input('reject_reason'),
        ]);
        AuditLogger::log('client.delete_rejected', $client, $before, $client->toArray(), [
            'client_name' => $client->name,
            'reviewed_by' => auth()->user()->name ?? 'HO',
            'reason' => $request->input('reject_reason'),
        ]);


        return redirect()->route('dev.clients.index')->with('success', 'Penghapusan client ditolak.');
    }
public function clientProjects($id)
    {
        try {
            $client = Client::findOrFail($id);
            $projects = $client->projects()
                ->withCount('rabs')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('dev.clients.projects', compact('client', 'projects'));

        } catch (\Exception $e) {
            Log::error('Client Projects Error:', [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal memuat data projects client: ' . $e->getMessage());
        }
    }

    /**
     * Show client reports
     */
    public function clientReports($id)
    {
        try {
            $client = Client::findOrFail($id);
            $client->load(['projects.rabs.items']);

            $stats = [
                'total_projects' => $client->projects->count(),
                'active_projects' => $client->projects->where('status', 'Active')->count(),
                'total_budget' => $client->projects->sum('budget'),
                'total_rab_value' => $client->projects->flatMap->rabs->sum('total_budget')
            ];

            return view('dev.clients.reports', compact('client', 'stats'));

        } catch (\Exception $e) {
            Log::error('Client Reports Error:', [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal memuat laporan client: ' . $e->getMessage());
        }
    }

    /**
     * Client reports overview
     */
    public function reports()
    {
        try {
            $clients = Client::withCount('projects')->get();
            
            $stats = [
                'total_clients' => $clients->count(),
                'clients_with_projects' => $clients->where('projects_count', '>', 0)->count(),
                'corporate_clients' => $clients->where('category', 'Corporate')->count(),
                'government_clients' => $clients->where('category', 'Government')->count(),
                'individual_clients' => $clients->where('category', 'Individual')->count()
            ];

            return view('dev.reports.clients', compact('clients', 'stats'));

        } catch (\Exception $e) {
            Log::error('Clients Reports Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat laporan clients: ' . $e->getMessage());
        }
    }

    /**
     * API for clients index (for AJAX calls)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Client::query();
            
            if ($request->has('search') && $request->search != '') {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('company', 'like', '%' . $request->search . '%');
            }

            $clients = $query->orderBy('name')->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $clients
            ]);

        } catch (\Exception $e) {
            Log::error('Clients API Index Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API for clients search
     */
    public function apiSearch(Request $request)
    {
        try {
            $search = $request->get('q', '');
            
            $clients = Client::where('name', 'like', '%' . $search . '%')
                ->orWhere('company', 'like', '%' . $search . '%')
                ->orderBy('name')
                ->limit(10)
                ->get();

            $formattedClients = $clients->map(function($client) {
                return [
                    'id' => $client->id,
                    'text' => $client->name . ($client->company ? ' (' . $client->company . ')' : ''),
                    'company' => $client->company,
                    'category' => $client->category
                ];
            });

            return response()->json([
                'success' => true,
                'results' => $formattedClients
            ]);

        } catch (\Exception $e) {
            Log::error('Clients API Search Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions for clients
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:delete,update_category',
                'ids' => 'required|array',
                'ids.*' => 'exists:clients,id'
            ]);

            $ids = $request->ids;
            $action = $request->action;
            $message = '';

            switch ($action) {
                case 'delete':
                    // Check if any client has projects
                    $clientsWithProjects = Client::whereIn('id', $ids)
                        ->whereHas('projects')
                        ->count();
                    
                    if ($clientsWithProjects > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Tidak dapat menghapus client yang masih memiliki project'
                        ], 400);
                    }
                    
                    Client::whereIn('id', $ids)->delete();
                    $message = count($ids) . ' client berhasil dihapus';
                    break;
                    
                case 'update_category':
                    $request->validate([
                        'new_category' => 'required|in:Corporate,Government,Individual'
                    ]);
                    
                    Client::whereIn('id', $ids)->update(['category' => $request->new_category]);
                    $message = count($ids) . ' client berhasil diupdate kategorinya';
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Aksi tidak valid'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in client bulkAction: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan aksi massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export clients to Excel
     */
    public function exportClients(Request $request)
    {
        try {
            $query = Client::withCount('projects');
            
            // Apply filters
            if ($request->has('category') && $request->category != '') {
                $query->where('category', $request->category);
            }

            $clients = $query->orderBy('name')->get();

            // TODO: Implement Excel export using Laravel Excel
            // Untuk sementara return info message
            
            Log::info('ðŸ“Š CLIENTS EXPORT REQUESTED:', ['count' => $clients->count()]);

            return back()->with('info', 'Fitur export Excel akan segera tersedia. Total clients: ' . $clients->count());

        } catch (\Exception $e) {
            Log::error('âŒ CLIENTS EXPORT ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    /**
     * Get client statistics
     */
    public function getClientStatistics()
    {
        try {
            $stats = [
                'total_clients' => Client::count(),
                'corporate_clients' => Client::where('category', 'Corporate')->count(),
                'government_clients' => Client::where('category', 'Government')->count(),
                'individual_clients' => Client::where('category', 'Individual')->count(),
                'clients_with_projects' => Client::has('projects')->count(),
                'clients_without_projects' => Client::doesntHave('projects')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Client Statistics API Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


