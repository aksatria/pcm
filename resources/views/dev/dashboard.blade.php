@extends('layouts.dev')

@section('title', 'Dashboard')
@section('subtitle', 'Project Cost Management Overview')

@section('content')
<div class="space-y-6 animate-fade-in px-0">
    <!-- Statistics Cards (match /dev/projects style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            [
                'title' => 'Total Clients',
                'value' => $stats['total_clients'] ?? 0,
                'description' => 'Active clients',
                'color' => 'green',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
            ],
            [
                'title' => 'Total Projects',
                'value' => $stats['total_projects'] ?? 0,
                'description' => ($stats['active_projects'] ?? 0) . ' active, ' . ($stats['completed_projects'] ?? 0) . ' completed',
                'color' => 'blue',
                'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'
            ],
            [
                'title' => 'Total RAB',
                'value' => $stats['total_rabs'] ?? 0,
                'description' => 'Budget plans',
                'color' => 'purple',
                'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'
            ],
            [
                'title' => 'Master Data',
                'value' => $stats['total_data'] ?? 0,
                'description' => 'Items in database',
                'color' => 'orange',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
            ]
        ] as $stat)
        <div class="group relative min-h-[120px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-1.5 rounded-lg backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-2 flex-shrink-0">
                        <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-{{ $stat['color'] }}-100 text-xs">{{ $stat['description'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @php
        $pendingApprovals = $pendingApprovals ?? [];
        $pendingTotal = array_sum($pendingApprovals);
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Approvals</h3>
                <span id="dashboardApprovalBadge" class="{{ $pendingTotal > 0 ? '' : 'hidden' }} text-xs bg-amber-500/90 text-white px-2 py-0.5 rounded-full">{{ $pendingTotal }}</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $pendingTotal }}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm w-full">
            <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                <div class="text-xs text-blue-700 dark:text-blue-200">SPP</div>
                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">{{ $pendingApprovals['spp'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-3">
                <div class="text-xs text-emerald-700 dark:text-emerald-200">BPG</div>
                <div class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">{{ $pendingApprovals['bpg'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                <div class="text-xs text-amber-700 dark:text-amber-200">LPB</div>
                <div class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ $pendingApprovals['lpb'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-3">
                <div class="text-xs text-violet-700 dark:text-violet-200">PO</div>
                <div class="text-lg font-semibold text-violet-900 dark:text-violet-100">{{ $pendingApprovals['po'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20 p-3">
                <div class="text-xs text-rose-700 dark:text-rose-200">SPK</div>
                <div class="text-lg font-semibold text-rose-900 dark:text-rose-100">{{ $pendingApprovals['spk'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 p-3">
                <div class="text-xs text-sky-700 dark:text-sky-200">Komparasi</div>
                <div class="text-lg font-semibold text-sky-900 dark:text-sky-100">{{ $pendingApprovals['komparasi'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20 p-3">
                <div class="text-xs text-orange-700 dark:text-orange-200">Voucher Pembelian</div>
                <div class="text-lg font-semibold text-orange-900 dark:text-orange-100">{{ $pendingApprovals['purchase_voucher'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    @if(app()->environment('local'))
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Realtime Test Panel</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kirim notifikasi test untuk verifikasi Reverb.</p>
            </div>
            <form method="POST" action="{{ route('dev.notifications.test') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Send Test Notification
                </button>
            </form>
        </div>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Pastikan `php artisan reverb:start` berjalan di lokal.
        </div>
    </div>
    @endif

    <!-- Charts Grid - 4 CHARTS LENGKAP -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Projects Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projects Overview</h3>
                <div class="flex space-x-2">
                    <button id="projectsMonthlyBtn" class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg">Monthly</button>
                    <button id="projectsQuarterlyBtn" class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg">Quarterly</button>
                </div>
            </div>
            
            <!-- Chart Container -->
            <div class="h-64">
                <canvas id="projectsChart"></canvas>
            </div>
            
            <!-- Chart Legend -->
            <div class="flex flex-wrap gap-3 mt-4 justify-center text-xs">
                @foreach($chartData['projects_by_status'] as $status => $count)
                <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 rounded-full 
                        @if($status === 'Planning') bg-yellow-500
                        @elseif($status === 'Active') bg-green-500
                        @elseif($status === 'On Hold') bg-orange-500
                        @elseif($status === 'Completed') bg-blue-500
                        @elseif($status === 'Cancelled') bg-red-500
                        @else bg-gray-500 @endif">
                    </div>
                    <span class="text-gray-600 dark:text-gray-400">{{ $status }}: {{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Chart 2: Clients Analytics -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Clients Analytics</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['total_clients'] ?? 0 }} total</span>
            </div>
            
            <!-- Chart Container -->
            <div class="h-64">
                <canvas id="clientsChart"></canvas>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm font-medium text-green-800 dark:text-green-300">New</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">5</p>
                </div>
                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Active</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $stats['total_clients'] ?? 0 }}</p>
                </div>
                <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Projects</p>
                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $stats['total_projects'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Chart 3: RAB Financial Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">RAB Financial Overview</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['total_rabs'] ?? 0 }} RABs</span>
            </div>
            
            <!-- Chart Container -->
            <div class="h-64">
                <canvas id="rabChart"></canvas>
            </div>
            
            <!-- Budget Summary -->
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Total Budget</span>
                    <span class="font-medium text-gray-900 dark:text-white">Rp 2.5M</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Avg. per Project</span>
                    <span class="font-medium text-gray-900 dark:text-white">Rp 625K</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Active Budgets</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $stats['total_rabs'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Chart 4: Data Master Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Data Master Distribution</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['total_data'] ?? 0 }} items</span>
            </div>
            
            <!-- Chart Container -->
            <div class="h-64">
                <canvas id="dataChart"></canvas>
            </div>
            
            <!-- Categories -->
            <div class="flex flex-wrap gap-2 mt-4 justify-center">
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">Material</span>
                <span class="px-2 py-1 text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 rounded">Jasa</span>
                <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 rounded">Peralatan</span>
                <span class="px-2 py-1 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded">Transportasi</span>
            </div>
        </div>
    </div>

    <!-- Recent Activities & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activities</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($recentActivities) }} activities</span>
            </div>
            <div class="space-y-4">
                @forelse($recentActivities as $activity)
                <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center 
                        @if($activity['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400
                        @elseif($activity['color'] === 'blue') bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400
                        @elseif($activity['color'] === 'purple') bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400
                        @else bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-400 @endif">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($activity['icon'] === 'users')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            @elseif($activity['icon'] === 'folder')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            @elseif($activity['icon'] === 'database')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $activity['description'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $activity['time']->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No recent activities</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('dev.clients.create') }}" class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-800/30 transition-colors duration-200 text-center group">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Add Client</span>
                </a>

                <a href="{{ route('dev.projects.create') }}" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl hover:bg-green-100 dark:hover:bg-green-800/30 transition-colors duration-200 text-center group">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-green-800 dark:text-green-300">New Project</span>
                </a>

                <a href="{{ route('dev.data.create') }}" class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-800/30 transition-colors duration-200 text-center group">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-purple-800 dark:text-purple-300">Add Data</span>
                </a>

                <a href="{{ route('dev.reports.dataMaster') }}" class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-800/30 transition-colors duration-200 text-center group">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-orange-800 dark:text-orange-300">Reports</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- Chart.js CSS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari controller
    const projectsByStatus = @json($chartData['projects_by_status']);
    const monthlyProjects = @json($chartData['monthly_projects']);
    
    // Mock data untuk charts lainnya (dalam real app, ini dari controller)
    const clientsData = {
        'Corporate': 8,
        'Individual': 4,
        'Government': 2
    };
    
    const rabData = {
        'Material': 45,
        'Labor': 30,
        'Equipment': 15,
        'Other': 10
    };
    
    const dataCategories = {
        'Material': 45,
        'Jasa': 23,
        'Peralatan': 15,
        'Transportasi': 12,
        'Lainnya': 8
    };

    // Function to get color based on status
    function getStatusColor(status) {
        const colors = {
            'Planning': 'rgba(255, 193, 7, 0.8)',
            'Active': 'rgba(40, 167, 69, 0.8)',
            'On Hold': 'rgba(253, 126, 20, 0.8)',
            'Completed': 'rgba(23, 162, 184, 0.8)',
            'Cancelled': 'rgba(220, 53, 69, 0.8)',
            'Corporate': 'rgba(59, 130, 246, 0.8)',
            'Individual': 'rgba(16, 185, 129, 0.8)',
            'Government': 'rgba(139, 92, 246, 0.8)',
            'Material': 'rgba(59, 130, 246, 0.8)',
            'Labor': 'rgba(16, 185, 129, 0.8)',
            'Equipment': 'rgba(245, 158, 11, 0.8)',
            'Other': 'rgba(156, 163, 175, 0.8)',
            'Jasa': 'rgba(16, 185, 129, 0.8)',
            'Peralatan': 'rgba(245, 158, 11, 0.8)',
            'Transportasi': 'rgba(139, 92, 246, 0.8)',
            'Lainnya': 'rgba(156, 163, 175, 0.8)'
        };
        return colors[status] || 'rgba(108, 117, 125, 0.8)';
    }

    // Chart 1: Projects Overview
    const projectsCtx = document.getElementById('projectsChart').getContext('2d');
    let projectsChart = new Chart(projectsCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(projectsByStatus),
            datasets: [{
                label: 'Projects',
                data: Object.values(projectsByStatus),
                backgroundColor: Object.keys(projectsByStatus).map(status => getStatusColor(status)),
                borderColor: Object.keys(projectsByStatus).map(status => getStatusColor(status).replace('0.8', '1')),
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Projects by Status',
                    color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#D1D5DB' : '#6B7280',
                    font: { size: 14, weight: '600' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'rgba(55, 65, 81, 0.5)' : 'rgba(229, 231, 235, 0.8)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Chart 2: Clients Analytics
    const clientsCtx = document.getElementById('clientsChart').getContext('2d');
    new Chart(clientsCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(clientsData),
            datasets: [{
                data: Object.values(clientsData),
                backgroundColor: Object.keys(clientsData).map(type => getStatusColor(type)),
                borderColor: 'transparent',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true }
                }
            },
            cutout: '60%'
        }
    });

    // Chart 3: RAB Financial Overview
    const rabCtx = document.getElementById('rabChart').getContext('2d');
    new Chart(rabCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(rabData),
            datasets: [{
                data: Object.values(rabData),
                backgroundColor: Object.keys(rabData).map(cat => getStatusColor(cat)),
                borderColor: 'transparent',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true }
                }
            }
        }
    });

    // Chart 4: Data Master Distribution
    const dataCtx = document.getElementById('dataChart').getContext('2d');
    new Chart(dataCtx, {
        type: 'polarArea',
        data: {
            labels: Object.keys(dataCategories),
            datasets: [{
                data: Object.values(dataCategories),
                backgroundColor: Object.keys(dataCategories).map(cat => getStatusColor(cat)),
                borderColor: 'transparent',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true }
                }
            },
            scales: {
                r: {
                    ticks: { display: false }
                }
            }
        }
    });

    // Projects Chart Toggle
    const projectsMonthlyBtn = document.getElementById('projectsMonthlyBtn');
    const projectsQuarterlyBtn = document.getElementById('projectsQuarterlyBtn');

    projectsMonthlyBtn.addEventListener('click', function() {
        projectsChart.destroy();
        projectsChart = new Chart(projectsCtx, {
            type: 'line',
            data: {
                labels: Object.keys(monthlyProjects),
                datasets: [{
                    label: 'Projects Created',
                    data: Object.values(monthlyProjects),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Monthly Projects Trend',
                        color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#D1D5DB' : '#6B7280',
                        font: { size: 14, weight: '600' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Update button styles
        projectsMonthlyBtn.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-800', 'dark:text-blue-300');
        projectsMonthlyBtn.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-400');
        projectsQuarterlyBtn.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-400');
        projectsQuarterlyBtn.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-800', 'dark:text-blue-300');
    });

    projectsQuarterlyBtn.addEventListener('click', function() {
        projectsChart.destroy();
        projectsChart = new Chart(projectsCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(projectsByStatus),
                datasets: [{
                    label: 'Projects',
                    data: Object.values(projectsByStatus),
                    backgroundColor: Object.keys(projectsByStatus).map(status => getStatusColor(status)),
                    borderColor: Object.keys(projectsByStatus).map(status => getStatusColor(status).replace('0.8', '1')),
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Projects by Status',
                        color: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#D1D5DB' : '#6B7280',
                        font: { size: 14, weight: '600' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Update button styles
        projectsQuarterlyBtn.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-800', 'dark:text-blue-300');
        projectsQuarterlyBtn.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-400');
        projectsMonthlyBtn.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-400');
        projectsMonthlyBtn.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-800', 'dark:text-blue-300');
    });
});
</script>
@endpush
@endsection
