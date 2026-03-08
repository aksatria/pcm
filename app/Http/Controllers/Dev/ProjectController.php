<?php
// app/Http\Controllers\Dev\ProjectController.php

namespace App\Http\Controllers\Dev;

use App\Models\Project;
use App\Models\Client;
use App\Models\ProjectFile;
use App\Models\Province;
use App\Models\Rab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProjectController extends BaseController
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


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Project::with(['client', 'province', 'rabs']);
            
            // Apply search
            $searchableFields = ['name', 'code', 'location', 'description', 'pic', 'client.name', 'client.company'];
            $query = $this->applySearch($query, $request->search, $searchableFields);
            
            // Apply filters
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('client_id') && $request->client_id != '') {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('province_id') && $request->province_id != '') {
                $query->where('province_id', $request->province_id);
            }
            
            // Apply sorting
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
                    case 'start_date_asc':
                        $query->orderBy('start_date', 'asc');
                        break;
                    case 'start_date_desc':
                        $query->orderBy('start_date', 'desc');
                        break;
                    case 'budget_high':
                        $query->orderBy('budget', 'desc');
                        break;
                    case 'budget_low':
                        $query->orderBy('budget', 'asc');
                        break;
                    default:
                        $query->latest();
                        break;
                }
            } else {
                $query->latest();
            }
            
            $projects = $query->paginate(10);
            $clients = Client::orderBy('name')->get();
            $provinces = Province::orderBy('name')->get();
            
            return view('dev.projects.index', compact('projects', 'clients', 'provinces'));

        } catch (\Exception $e) {
            Log::error('Project Index Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat data projects: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $clients = Client::orderBy('name')->get();
            $statuses = ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'];
            $defaultCode = $this->generateProjectCode();
            $provinces = Province::orderBy('name')->get();
            
            return view('dev.projects.create', compact('clients', 'statuses', 'defaultCode', 'provinces'));

        } catch (\Exception $e) {
            Log::error('Project Create Form Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form project: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('🎯 PROJECT STORE START');
        
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'client_id' => 'required|exists:clients,id',
                'location' => 'required|string|max:255',
                'province_id' => 'nullable|exists:provinces,id',
                'start_date' => 'required|date',
                'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
            ], [
                'name.required' => 'Nama project wajib diisi',
                'client_id.required' => 'Client wajib dipilih',
                'location.required' => 'Lokasi project wajib diisi',
                'start_date.required' => 'Tanggal mulai wajib diisi',
                'start_date.date' => 'Format tanggal mulai tidak valid',
                'files.*.max' => 'File tidak boleh lebih dari 10MB',
                'files.*.mimes' => 'Format file harus: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, txt',
            ]);

            if ($request->end_date && $request->end_date < $request->start_date) {
                return back()->with('error', 'Tanggal selesai tidak boleh sebelum tanggal mulai')->withInput();
            }

            DB::beginTransaction();

            $project = Project::create([
                'code' => $this->generateUniqueProjectCode(),
                'name' => $request->name,
                'client_id' => $request->client_id,
                'province_id' => $request->province_id,
                'location' => $request->location,
                'pic' => $request->pic,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'budget' => $this->parseCurrency($request->budget),
                'status' => $request->status ?? 'Planning',
                'description' => $request->description,
            ]);

            // Create initial RAB for the project
            $this->createInitialRab($project);

            if ($request->hasFile('files')) {
                $this->handleFileUploads($project, $request->file('files'), $request->file_types ?? []);
            }

            DB::commit();

            Log::info('🎉 PROJECT CREATED:', ['id' => $project->id, 'name' => $project->name]);

            return redirect()->route('dev.projects.index')
                ->with('success', 'Project berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ PROJECT CREATE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
 * Display the specified resource.
 */
public function show($id)
{
    try {
        // Cari project dengan ID, atau return 404 jika tidak ditemukan
        $project = Project::with(['client', 'province', 'files', 'rabs.items.data'])->findOrFail($id);
        
        // Handle jika client tidak ditemukan
        if (!$project->client) {
            Log::warning('Client not found for project', ['project_id' => $project->id]);
        }

        return view('dev.projects.show', compact('project'));

    } catch (\Exception $e) {
        Log::error('Project Show Error:', [
            'project_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Project tidak ditemukan: ' . $e->getMessage());
    }
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        try {
            $clients = Client::orderBy('name')->get();
            $statuses = ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'];
            $provinces = Province::orderBy('name')->get();
            
            return view('dev.projects.edit', compact('project', 'clients', 'statuses', 'provinces'));

        } catch (\Exception $e) {
            Log::error('Project Edit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit project: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        Log::info('🎯 PROJECT UPDATE METHOD DIPANGGIL');
        
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'client_id' => 'required|exists:clients,id',
                'location' => 'required|string|max:255',
                'province_id' => 'nullable|exists:provinces,id',
                'start_date' => 'required|date',
                'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
            ], [
                'name.required' => 'Nama project wajib diisi',
                'client_id.required' => 'Client wajib dipilih',
                'location.required' => 'Lokasi project wajib diisi',
                'start_date.required' => 'Tanggal mulai wajib diisi',
                'start_date.date' => 'Format tanggal mulai tidak valid',
                'files.*.max' => 'File tidak boleh lebih dari 10MB',
                'files.*.mimes' => 'Format file harus: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, txt',
            ]);

            if ($request->end_date && $request->end_date < $request->start_date) {
                return back()->with('error', 'Tanggal selesai tidak boleh sebelum tanggal mulai')->withInput();
            }

            DB::beginTransaction();

            $budget = $this->parseCurrency($request->budget);

            $project->update([
                'name' => $request->name,
                'client_id' => $request->client_id,
                'province_id' => $request->province_id,
                'location' => $request->location,
                'pic' => $request->pic,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'budget' => $budget,
                'status' => $request->status ?? 'Planning',
                'description' => $request->description,
            ]);

            if ($request->hasFile('files')) {
                $this->handleFileUploads($project, $request->file('files'), $request->file_types ?? []);
            }

            DB::commit();

            Log::info('🎉 PROJECT UPDATED:', ['id' => $project->id, 'name' => $project->name]);

            return redirect()->route('dev.projects.show', $project)
                ->with('success', 'Project berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ PROJECT UPDATE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\Illuminate\Http\Request $request, Project $project)
    {
        $__ho = $this->ensureHO($request);
        if ($__ho) { return $__ho; }

        Log::info('🗑️ PROJECT DESTROY METHOD DIPANGGIL');
        
        try {
            DB::beginTransaction();

            $projectName = $project->name;
            
            // Delete all files
            foreach ($project->files as $file) {
                Storage::disk('public')->delete($file->file_path);
                $file->delete();
            }
            
            // Delete all RABs and their items
            foreach ($project->rabs as $rab) {
                $rab->items()->delete();
                $rab->delete();
            }
            
            $project->delete();

            DB::commit();
            
            Log::info('🗑️ PROJECT DELETED:', ['id' => $project->id, 'name' => $projectName]);

            return redirect()->route('dev.projects.index')
                ->with('success', 'Project "' . $projectName . '" berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ PROJECT DELETE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete project file
     */
    public function deleteFile(\Illuminate\Http\Request $request, ProjectFile $file)
    {
        $__ho = $this->ensureHO($request);
        if ($__ho) { return $__ho; }

        try {
            $fileName = $file->original_name;
            
            Storage::disk('public')->delete($file->file_path);
            $file->delete();
            
            Log::info('🗑️ FILE DELETED:', ['file_name' => $fileName]);

            return back()->with('success', 'File "' . $fileName . '" berhasil dihapus!');

        } catch (\Exception $e) {
            Log::error('❌ FILE DELETE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Project budget page
     */
    public function budget(Project $project)
    {
        try {
            $project->load(['client', 'rabs.items.data']);
            
            return view('dev.projects.budget', compact('project'));

        } catch (\Exception $e) {
            Log::error('Project Budget Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat halaman budget: ' . $e->getMessage());
        }
    }

    /**
     * Update project budget
     */
    public function updateBudget(Request $request, Project $project)
    {
        try {
            $request->validate([
                'budget' => 'required|numeric|min:0|max:999999999999',
                'notes' => 'nullable|string|max:1000'
            ]);

            $oldBudget = $project->budget;
            $newBudget = $this->parseCurrency($request->budget);

            $project->update([
                'budget' => $newBudget
            ]);

            Log::info('💰 PROJECT BUDGET UPDATED:', [
                'project_id' => $project->id,
                'old_budget' => $oldBudget,
                'new_budget' => $newBudget,
                'notes' => $request->notes
            ]);

            return back()->with('success', 'Budget project berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('❌ PROJECT BUDGET UPDATE ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui budget: ' . $e->getMessage());
        }
    }

    /**
     * Project timeline page
     */
    public function timeline(Project $project)
    {
        try {
            $project->load(['client']);
            
            return view('dev.projects.timeline', compact('project'));

        } catch (\Exception $e) {
            Log::error('Project Timeline Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat timeline project: ' . $e->getMessage());
        }
    }

    /**
     * Project reports page
     */
    public function projectReports(Project $project)
    {
        try {
            $project->load([
                'client',
                'province',
                'files',
                'rabs.items.data'
            ]);

            $rabStatistics = $project->getRabStatistics();
            
            return view('dev.projects.reports', compact('project', 'rabStatistics'));

        } catch (\Exception $e) {
            Log::error('Project Reports Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat laporan project: ' . $e->getMessage());
        }
    }

    /**
     * General projects reports
     */
    public function reports(Request $request)
    {
        try {
            $query = Project::with(['client', 'province', 'rabs']);
            
            // Apply filters
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('client_id') && $request->client_id != '') {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('date_from') && $request->date_from != '') {
                $query->whereDate('start_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to != '') {
                $query->whereDate('start_date', '<=', $request->date_to);
            }

            $projects = $query->orderBy('created_at', 'desc')->get();
            
            $stats = Project::getStatistics();
            $projectsByStatus = Project::getProjectsByStatus();
            $projectsByProvince = Project::getProjectsByProvince();

            $clients = Client::orderBy('name')->get();
            $provinces = Province::orderBy('name')->get();
            
            return view('dev.projects.general-reports', compact(
                'projects', 
                'stats', 
                'projectsByStatus', 
                'projectsByProvince',
                'clients',
                'provinces'
            ));

        } catch (\Exception $e) {
            Log::error('Projects General Reports Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat laporan projects: ' . $e->getMessage());
        }
    }

    /**
     * API for projects index (for AJAX calls)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Project::with(['client', 'province']);
            
            if ($request->has('search') && $request->search != '') {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('code', 'like', '%' . $request->search . '%');
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            $projects = $query->orderBy('name')->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $projects
            ]);

        } catch (\Exception $e) {
            Log::error('Projects API Index Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API for projects search
     */
    public function apiSearch(Request $request)
    {
        try {
            $search = $request->get('q', '');
            
            $projects = Project::with(['client'])
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhereHas('client', function($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->orderBy('name')
                ->limit(10)
                ->get();

            $formattedProjects = $projects->map(function($project) {
                return [
                    'id' => $project->id,
                    'text' => $project->name . ' (' . $project->code . ') - ' . ($project->client->name ?? 'No Client'),
                    'client' => $project->client->name ?? 'No Client',
                    'code' => $project->code
                ];
            });

            return response()->json([
                'success' => true,
                'results' => $formattedProjects
            ]);

        } catch (\Exception $e) {
            Log::error('Projects API Search Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project statistics for dashboard
     */
    public function getProjectStatistics()
    {
        try {
            $stats = Project::getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Project Statistics API Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export projects to Excel
     */
    public function exportProjects(Request $request)
    {
        try {
            $query = Project::with(['client', 'province', 'rabs']);
            
            // Apply filters sama seperti index
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('client_id') && $request->client_id != '') {
                $query->where('client_id', $request->client_id);
            }

            $projects = $query->orderBy('created_at', 'desc')->get();

            // TODO: Implement Excel export using Laravel Excel
            // Untuk sementara return info message
            
            Log::info('📊 PROJECTS EXPORT REQUESTED:', ['count' => $projects->count()]);

            return back()->with('info', 'Fitur export Excel akan segera tersedia. Total projects: ' . $projects->count());

        } catch (\Exception $e) {
            Log::error('❌ PROJECTS EXPORT ERROR:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Handle file uploads for project
     */
    private function handleFileUploads($project, $files, $fileTypes)
    {
        foreach ($files as $index => $file) {
            if ($file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = 'project_' . $project->id . '_' . time() . '_' . $index . '.' . $extension;
                
                $filePath = $file->storeAs('project_files', $filename, 'public');
                
                $fileType = $this->getFileType($fileTypes, $index, $originalName);
                
                ProjectFile::create([
                    'project_id' => $project->id,
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'file_path' => $filePath,
                    'file_type' => $fileType,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
                
                Log::info('📁 FILE UPLOADED:', ['filename' => $filename]);
            }
        }
    }

    /**
     * Generate project code
     */
    private function generateProjectCode(): string
    {
        return $this->generateUniqueCode(new Project, 'PROJ', 'code', 3);
    }

    /**
     * Generate unique project code
     */
    private function generateUniqueProjectCode(): string
    {
        return $this->generateUniqueCode(new Project, 'PROJ', 'code', 3);
    }

    /**
     * Create initial RAB for new project
     */
    private function createInitialRab(Project $project)
    {
        try {
            $rabName = 'RAB ' . $project->name;

            $rab = Rab::create([
                'project_id' => $project->id,
                'name' => $rabName,
                'version' => '1.0',
                'status' => 'draft',
                'notes' => 'Rencana Anggaran Biaya awal untuk project ' . $project->name,
                'total_budget' => 0,
                'breakdown' => []
            ]);

            Log::info('✅ INITIAL RAB CREATED:', [
                'project_id' => $project->id,
                'rab_id' => $rab->id
            ]);

            return $rab;

        } catch (\Exception $e) {
            Log::error('❌ INITIAL RAB CREATION ERROR:', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get file type based on filename or user selection
     */
    protected function getFileType($fileTypes, $index, $originalName)
    {
        if (isset($fileTypes[$index]) && !empty($fileTypes[$index])) {
            return $fileTypes[$index];
        }
        
        $name = strtolower($originalName);
        if (str_contains($name, 'surat') || str_contains($name, 'perintah')) {
            return 'surat_perintah';
        } elseif (str_contains($name, 'kontrak')) {
            return 'kontrak';
        } elseif (str_contains($name, 'proposal')) {
            return 'proposal';
        } elseif (str_contains($name, 'laporan')) {
            return 'laporan';
        }
        
        return 'lainnya';
    }
}