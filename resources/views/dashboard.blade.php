<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\MasterData;
use App\Models\Client;
use App\Models\Project;
// use App\Models\Rab;
// use App\Models\Rapp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display dashboard with statistics
     */
    public function index()
    {
        try {
            // Get comprehensive statistics for dashboard
            $stats = $this->getDashboardStatistics();

            return view('dev.dashboard', compact('stats'));

        } catch (\Exception $e) {
            \Log::error('Error loading dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat dashboard');
        }
    }

    /**
     * Get comprehensive dashboard statistics
     */
    private function getDashboardStatistics()
    {
        return [
            // Client Statistics
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'active')->count(),
            'new_clients_this_month' => Client::whereMonth('created_at', now()->month)->count(),
            
            // Project Statistics
            'total_projects' => Project::count(),
            'ongoing_projects' => Project::where('status', 'ongoing')->count(),
            'completed_projects' => Project::where('status', 'completed')->count(),
            'recent_projects' => Project::with('client')
                ->latest()
                ->take(5)
                ->get(),
            
            // Master Data Statistics
            'total_master_data' => MasterData::active()->count(),
            'master_data_by_category' => MasterData::active()
                ->select('kategori', DB::raw('COUNT(*) as count'))
                ->groupBy('kategori')
                ->get()
                ->pluck('count', 'kategori'),
            'recent_master_data_updates' => MasterData::active()
                ->where('tanggal_update', '>=', Carbon::now()->subDays(7))
                ->count(),
            'master_data_stats' => MasterData::getDashboardStatistics(),
            
            // RAB Statistics
            'total_rab_items' => Rab::count(),
            'total_rab_value' => Rab::sum('total_harga') ?? 0,
            'average_rab_value' => Rab::avg('total_harga') ?? 0,
            
            // RAPP Statistics
            'total_rapps' => Rapp::count(),
            'pending_rapps' => Rapp::where('status', 'pending')->count(),
            'approved_rapps' => Rapp::where('status', 'approved')->count(),
        ];
    }

    /**
     * Get dashboard statistics for API
     */
    public function getStats()
    {
        try {
            $stats = $this->getDashboardStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik'
            ], 500);
        }
    }
}

