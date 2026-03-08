@extends('layouts.dev')

@section('title', 'RAB Breakdown Detail - ' . $rabBreakdown->rab_breakdown_code . ' - ' . $project->name)

@section('content')
@php
    $isHO = auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
    $canEdit = method_exists($rabBreakdown, 'canEdit') ? $rabBreakdown->canEdit() : $isHO;
    $approvalKey = method_exists($rabBreakdown, 'approvalKey') ? $rabBreakdown->approvalKey() : strtolower((string) ($rabBreakdown->approval_status ?? 'draft'));
    $wbsTopCode = null;
    if (preg_match('/^RAB-(\d{3})$/i', (string) $rabBreakdown->rab_breakdown_code, $m) === 1) {
        $num = (int) $m[1];
        if ($num >= 1 && $num <= 26) {
            $wbsTopCode = chr(ord('A') + $num - 1) . '.';
        }
    }
@endphp
@include('dev.rab-breakdown.partials.shared-theme')
<style>
    /* RAB Breakdown Detail Layout */
    .rab-breakdown-detail-container {
        max-width: 1280px;
        margin: 0 auto;
    }

    /* Header Section */
    .rab-breakdown-header {
        background: #ffffff;
        color: #0f172a;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .rab-breakdown-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .rab-breakdown-title-section {
        flex: 1;
    }

    .rab-breakdown-code {
        background: #e0f2fe;
        color: #0369a1;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .rab-breakdown-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .rab-breakdown-description {
        font-size: 0.95rem;
        color: #475569;
        margin-bottom: 1rem;
    }

    .rab-breakdown-status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-not_started { background: #9ca3af; }
    .status-in_progress { background: #3b82f6; }
    .status-completed { background: #10b981; }
    .status-delayed { background: #ef4444; }
    .status-draft { background: #6b7280; }
    .status-submitted { background: #2563eb; }
    .status-approved { background: #059669; }
    .status-rejected { background: #dc2626; }

    /* Stats Grid */
    .rab-breakdown-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.1rem;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }

    /* Progress Section */
    .progress-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .progress-bar-container {
        height: 0.75rem;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 9999px;
        transition: width 0.6s ease;
    }

    .progress-details {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .action-buttons a,
    .action-buttons button {
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.5rem;
    }

    /* Items Table Styling */
    .items-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        margin-bottom: 1.5rem;
    }

    .section-header {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Category Group */
    .category-group {
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .category-header {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .category-content {
        padding: 0;
        background: white;
        overflow-x: auto;
    }

    .category-total {
        padding: 0.75rem 1rem;
        background: #f1f5f9;
        font-weight: 600;
        text-align: right;
        border-top: 1px solid #e5e7eb;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6b7280;
    }

    .empty-state-icon {
        width: 4rem;
        height: 4rem;
        color: #9ca3af;
        margin: 0 auto 1rem;
    }

    /* Dark Mode Support */
    .dark .rab-breakdown-header {
        background: #111827;
        color: #e5e7eb;
        border-color: #374151;
    }

    .dark .rab-breakdown-code {
        background: #1e3a8a;
        color: #bfdbfe;
    }

    .dark .rab-breakdown-description {
        color: #94a3b8;
    }

    .dark .stat-card {
        background: #1f2937;
        border-color: #374151;
    }

    .dark .stat-label {
        color: #94a3b8;
    }

    .dark .stat-value {
        color: #f9fafb;
    }

    .dark .progress-section,
    .dark .items-section {
        background: #111827;
        border-color: #374151;
    }

    .dark .section-header {
        background: #374151;
        border-color: #4b5563;
    }

    .dark .section-title {
        color: #f9fafb;
    }

    .dark .category-group {
        border-color: #374151;
    }

    .dark .category-header {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }

    .dark .category-content {
        background: #1f2937;
    }

.dark .category-total {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }

    /* Visual refresh: cleaner hierarchy + consistent surfaces */
    .page-nav {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.625rem 0.75rem;
    }

    .dark .page-nav {
        background: #111827;
        border-color: #374151;
    }

    .page-nav a {
        font-weight: 600;
    }

    .rab-breakdown-header {
        position: relative;
        overflow: hidden;
    }

    .rab-breakdown-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #0284c7, #2563eb);
    }

    .action-toolbar {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .dark .action-toolbar {
        background: #111827;
        border-color: #374151;
    }

    .action-buttons a,
    .action-buttons button {
        min-height: 34px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    .progress-header h2,
    .section-title {
        letter-spacing: 0.01em;
    }

    .category-header {
        background: #f8fafc;
    }

    .category-header:hover {
        background: #f1f5f9;
    }

    .dark .category-header:hover {
        background: #334155;
    }


    .fund-item {
        border: 1px solid #e5e7eb;
        border-left-width: 4px;
        border-radius: 0.5rem;
        background: #ffffff;
        padding: 0.65rem 0.75rem;
    }

    .dark .fund-item {
        background: #0f172a;
        border-color: #334155;
    }

    .fund-item-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .fund-code {
        font-size: 0.72rem;
        font-weight: 700;
        color: #0369a1;
        letter-spacing: 0.02em;
    }

    .fund-title {
        margin-top: 0.1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0f172a;
    }

    .dark .fund-title {
        color: #e5e7eb;
    }

    .fund-meta {
        margin-top: 0.45rem;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.4rem;
    }

    .fund-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.4rem;
        padding: 0.34rem 0.45rem;
    }

    .dark .fund-pill {
        background: #1e293b;
        border-color: #334155;
    }

    .fund-pill .k {
        font-size: 0.62rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
    }

    .fund-pill .v {
        margin-top: 0.08rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #0f172a;
    }

    .dark .fund-pill .v {
        color: #e5e7eb;
    }

    .hygiene-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .hygiene-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem 0.65rem;
        background: #f8fafc;
    }

    .hygiene-card .k {
        font-size: 0.66rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.03em;
        margin-bottom: 0.1rem;
    }

    .hygiene-card .v {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .hygiene-ok { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .hygiene-warning { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
    .hygiene-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    .audit-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.14rem 0.5rem;
        font-size: 0.62rem;
        font-weight: 700;
        line-height: 1;
        border: 1px solid;
        white-space: nowrap;
    }

    .audit-ok { background: #ecfdf5; color: #166534; border-color: #86efac; }
    .audit-warning { background: #fffbeb; color: #a16207; border-color: #fcd34d; }
    .audit-danger { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }

    .audit-issues {
        margin-top: 0.35rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }

    .audit-issue {
        font-size: 0.64rem;
        border-radius: 9999px;
        padding: 0.12rem 0.45rem;
        border: 1px dashed #cbd5e1;
        color: #475569;
        background: #f8fafc;
    }

    @media (max-width: 1024px) {
        .action-toolbar {
            flex-direction: column;
        }
    }

    @media (max-width: 640px) {
        .fund-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .hygiene-strip {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="rab-breakdown-detail-container">
    <!-- Back Navigation -->
    <div class="page-nav mb-4 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('dev.projects.index') }}"
           class="rb-btn-outline rb-btn-sm">
            Daftar Project
        </a>
        <a href="{{ route('dev.projects.show', $project->id) }}"
           class="rb-btn-outline rb-btn-sm">
            Detail Project
        </a>
        <a href="{{ route('dev.rab-baseline.index', $project->id) }}"
           class="rb-btn-outline rb-btn-sm">
            Daftar RAPP
        </a>
        <a href="{{ route('dev.rab-breakdown.index', $project->id) }}" 
           class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar RAB Breakdown
        </a>
    </div>

    <!-- RAB Breakdown Header -->
    <div class="rab-breakdown-header">
        <div class="rab-breakdown-header-top">
            <div class="rab-breakdown-title-section">
                <span class="rab-breakdown-code">{{ $wbsTopCode ?? ($rabBreakdown->rab_breakdown_code . '.') }}</span>
                <h1 class="rab-breakdown-name">{{ $rabBreakdown->name }}</h1>
                @if($rabBreakdown->description)
                    <p class="rab-breakdown-description">{{ $rabBreakdown->description }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="rab-breakdown-status-badge status-{{ $rabBreakdown->status }}">
                    Progress: {{ $rabBreakdown->status_label }}
                </span>
                @include('dev.rab-breakdown.partials.approval-chip', ['approvalKey' => $approvalKey, 'prefix' => 'APP'])
            </div>
        </div>

        <div class="space-y-2 mb-4">
            @if(($rabBreakdown->approval_status ?? '') === 'rejected' && !empty($rabBreakdown->rejected_reason))
                <div class="text-xs bg-white/15 px-3 py-2 rounded">
                    Alasan Reject: {{ $rabBreakdown->rejected_reason }}
                </div>
            @endif
            @if(!$canEdit)
                <div class="text-xs bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-200 px-3 py-2 rounded">
                    Mode terkunci untuk Staff karena status approval <b>{{ strtoupper($approvalKey) }}</b>. Perubahan item/header menunggu proses HO.
                </div>
            @elseif($approvalKey === 'draft' || $approvalKey === 'rejected')
                <div class="text-xs bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 text-sky-700 dark:text-sky-200 px-3 py-2 rounded">
                    Mode edit aktif. Setelah final, lakukan <b>Submit ke HO</b>.
                </div>
            @elseif($isHO && $approvalKey === 'submitted')
                <div class="text-xs bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-200 px-3 py-2 rounded">
                    Menunggu keputusan HO: lakukan <b>Approve</b> atau <b>Reject</b> setelah review.
                </div>
            @endif
        </div>

        <!-- Stats Grid -->
        <div class="rab-breakdown-stats-grid">
            <div class="stat-card">
                <div class="stat-label">Budget Total</div>
                <div class="stat-value">{{ $rabBreakdown->formatted_budget }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Actual Spend</div>
                <div class="stat-value">{{ $rabBreakdown->formatted_actual }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Progress</div>
                <div class="stat-value">{{ number_format($rabBreakdown->progress_percentage, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Items Count</div>
                <div class="stat-value">{{ $rabBreakdown->items->count() }} items</div>
            </div>
        </div>

        <!-- Timeline -->
        @if($rabBreakdown->start_date || $rabBreakdown->end_date)
            <div class="text-sm opacity-80">
                @if($rabBreakdown->start_date)
                    <span>{{ \Carbon\Carbon::parse($rabBreakdown->start_date)->format('d M Y') }}</span>
                    @if($rabBreakdown->end_date)
                        <span class="mx-2">-</span>
                        <span>{{ \Carbon\Carbon::parse($rabBreakdown->end_date)->format('d M Y') }}</span>
                    @endif
                @endif
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div class="action-toolbar">
        <div class="action-buttons">
            @if($canEdit)
                <a href="{{ route('dev.rab-breakdown.items.create', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                   class="rb-btn-primary rb-btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Item
                </a>
            @endif
            
            @if($canEdit)
                <a href="{{ route('dev.rab-breakdown.import.form', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                   class="rb-btn-outline rb-btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    Import dari Excel
                </a>
                
                <button onclick="exportRabBreakdown()"
                        class="rb-btn-outline rb-btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                        Export Excel
                </button>
            @endif
        </div>
        
        <div class="action-buttons">
            @if($canEdit)
                <a href="{{ route('dev.rab-breakdown.edit', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                   class="rb-btn-outline rb-btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit RAB Breakdown
                </a>
            @endif

            @if($canEdit)
                <form method="POST" action="{{ route('dev.rab-breakdown.data-hygiene', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Jalankan Data Hygiene? Sistem akan merapikan urutan item code dan audit anomali data.')"
                            class="rb-btn-warn rb-btn-sm">
                        Data Hygiene
                    </button>
                </form>
            @endif

            @if(in_array($approvalKey, ['draft', 'rejected'], true))
                <form method="POST" action="{{ route('dev.rab-breakdown.submit', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="rb-btn-primary rb-btn-sm">
                        Submit ke HO
                    </button>
                </form>
            @endif

            @if($isHO && $approvalKey === 'submitted')
                <form method="POST" action="{{ route('dev.rab-breakdown.approve', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="rb-btn-success rb-btn-sm">
                        Approve
                    </button>
                </form>
                <form method="POST" action="{{ route('dev.rab-breakdown.reject', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" class="inline">
                    @csrf
                    <input type="hidden" name="rejected_reason" value="">
                    <button type="submit"
                            onclick="const reason = prompt('Alasan reject RAB Breakdown (opsional):', 'Perlu revisi RAB Breakdown.'); if (reason === null) return false; this.form.rejected_reason.value = reason;"
                            class="rb-btn-danger rb-btn-sm">
                        Reject
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Progress Update Section -->
    <div class="progress-section">
        <div class="progress-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Update Progress</h2>
            <span class="text-sm text-gray-600 dark:text-gray-400">Last updated: {{ $rabBreakdown->updated_at->format('d M Y H:i') }}</span>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="width: {{ $rabBreakdown->progress_percentage }}%"></div>
        </div>
        
        <div class="progress-details">
            <span>0%</span>
            <span class="font-semibold">{{ number_format($rabBreakdown->progress_percentage, 1) }}% Complete</span>
            <span>100%</span>
        </div>
        
        <form action="{{ route('dev.rab-breakdown.update-progress', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
              method="POST" class="mt-4" id="progressForm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Actual Amount Spent
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">Rp</span>
                        </div>
                        <input type="number" 
                               name="actual_amount" 
                               value="{{ old('actual_amount', $rabBreakdown->actual_amount) }}"
                               @disabled(!$canEdit)
                               class="pl-12 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="0">
                    </div>
                </div>
                
                <div class="flex items-end">
                    <button type="submit"
                            @disabled(!$canEdit)
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        {{ $canEdit ? 'Update Progress' : ('Terkunci (' . strtoupper($approvalKey) . ')') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Work Breakdown Structure Section -->
    <div class="items-section">
        <div class="section-header">
            <div class="section-title">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Work Breakdown Structure (Data Fundamental)
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Total Sub Item: {{ $rabBreakdown->items->count() }}
            </div>
        </div>

        <div class="p-4">
            <div class="mb-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 px-3 py-2 text-[11px] text-slate-700 dark:text-slate-300">
                Tampilan ini hanya menampilkan data fundamental. Dimensi teknis (P/L/T/N/DIA/BERAT) dikelola di halaman <b>Edit Item</b>.
            </div>
            <div class="hygiene-strip">
                <div class="hygiene-card hygiene-ok">
                    <div class="k">Hygiene OK</div>
                    <div class="v">{{ $hygieneSummary['ok'] ?? 0 }} item</div>
                </div>
                <div class="hygiene-card hygiene-warning">
                    <div class="k">Perlu Cek</div>
                    <div class="v">{{ $hygieneSummary['warning'] ?? 0 }} item</div>
                </div>
                <div class="hygiene-card hygiene-danger">
                    <div class="k">Kritis</div>
                    <div class="v">{{ $hygieneSummary['danger'] ?? 0 }} item</div>
                </div>
            </div>
            @if($itemsByCategory->count() > 0)
                @foreach($itemsByCategory as $category => $items)
                    @php
                        $categoryTotal = $items->sum('total_price');
                        $paletteList = [
                            ['header' => 'bg-sky-50 dark:bg-sky-900/20 text-sky-900 dark:text-sky-200', 'border' => 'border-sky-300 dark:border-sky-700', 'accent' => 'border-l-sky-500', 'chip' => 'text-sky-700 dark:text-sky-300', 'subtotal' => 'bg-sky-50 dark:bg-sky-900/20'],
                            ['header' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-900 dark:text-emerald-200', 'border' => 'border-emerald-300 dark:border-emerald-700', 'accent' => 'border-l-emerald-500', 'chip' => 'text-emerald-700 dark:text-emerald-300', 'subtotal' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                            ['header' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200', 'border' => 'border-amber-300 dark:border-amber-700', 'accent' => 'border-l-amber-500', 'chip' => 'text-amber-700 dark:text-amber-300', 'subtotal' => 'bg-amber-50 dark:bg-amber-900/20'],
                            ['header' => 'bg-violet-50 dark:bg-violet-900/20 text-violet-900 dark:text-violet-200', 'border' => 'border-violet-300 dark:border-violet-700', 'accent' => 'border-l-violet-500', 'chip' => 'text-violet-700 dark:text-violet-300', 'subtotal' => 'bg-violet-50 dark:bg-violet-900/20'],
                            ['header' => 'bg-rose-50 dark:bg-rose-900/20 text-rose-900 dark:text-rose-200', 'border' => 'border-rose-300 dark:border-rose-700', 'accent' => 'border-l-rose-500', 'chip' => 'text-rose-700 dark:text-rose-300', 'subtotal' => 'bg-rose-50 dark:bg-rose-900/20'],
                            ['header' => 'bg-teal-50 dark:bg-teal-900/20 text-teal-900 dark:text-teal-200', 'border' => 'border-teal-300 dark:border-teal-700', 'accent' => 'border-l-teal-500', 'chip' => 'text-teal-700 dark:text-teal-300', 'subtotal' => 'bg-teal-50 dark:bg-teal-900/20'],
                        ];
                        $catLetter = strtoupper(substr((string) $category, 0, 1));
                        $catIndex = ord($catLetter) - ord('A');
                        if ($catIndex < 0) { $catIndex = 0; }
                        $palette = $paletteList[$catIndex % count($paletteList)];
                    @endphp
                    
                    <div class="category-group">
                        <div class="category-header {{ $palette['header'] }} {{ $palette['border'] }}" onclick="toggleCategory('{{ $category }}')">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 transform transition-transform" id="icon-{{ $category }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <span class="font-semibold {{ $palette['chip'] }}">Kelompok {{ $category }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/70 dark:bg-black/20">{{ $items->count() }} item</span>
                            </div>
                            <div class="font-mono font-semibold">
                                Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                            </div>
                        </div>
                        
                        <div class="category-content" id="content-{{ $category }}">
                            <div class="space-y-2 p-3">
                                @foreach($items as $item)
                                    @php
                                        $primarySource = $item->budgetSources->first();
                                        $qtyBeli = $item->qty_beli ?? optional($primarySource)->qty_beli;
                                        $jumlahAlokasi = $item->jumlah ?? optional($primarySource)->jumlah;
                                        $selisih = (float) ($jumlahAlokasi ?? 0) - (float) ($item->total_price ?? 0);
                                    @endphp
                                    <div class="fund-item {{ $palette['accent'] }}">
                                        <div class="fund-item-head">
                                            <div>
                                                <div class="fund-code">{{ $item->item_code }}</div>
                                                <div class="fund-title">{{ $item->uraian }}</div>
                                                @php
                                                    $audit = $item->hygiene_audit ?? ['severity' => 'ok', 'issues' => ['Data sehat']];
                                                    $severity = $audit['severity'] ?? 'ok';
                                                    $badgeClass = $severity === 'danger'
                                                        ? 'audit-danger'
                                                        : ($severity === 'warning' ? 'audit-warning' : 'audit-ok');
                                                @endphp
                                                <div class="audit-issues">
                                                    <span class="audit-badge {{ $badgeClass }}">
                                                        {{ strtoupper($severity) }}
                                                    </span>
                                                    @foreach(($audit['issues'] ?? []) as $issue)
                                                        <span class="audit-issue">{{ $issue }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="flex gap-1 shrink-0">
                                                @if($canEdit)
                                                    <button onclick="editItem({{ $item->id }})"
                                                            class="px-2 py-1 text-[11px] rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                            title="Edit Lengkap">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('dev.rab-breakdown.items.destroy', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id, 'item' => $item->id]) }}"
                                                          method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="px-2 py-1 text-[11px] rounded bg-red-600 hover:bg-red-700 text-white"
                                                                onclick="return confirm('Hapus item ini?')"
                                                                title="Hapus Item">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="fund-meta">
                                            <div class="fund-pill"><div class="k">Sat</div><div class="v">{{ $item->satuan ?? '-' }}</div></div>
                                            <div class="fund-pill"><div class="k">Vol</div><div class="v">{{ number_format((float) $item->volume_rab, 2, ',', '.') }}</div></div>
                                            <div class="fund-pill"><div class="k">Qty Beli</div><div class="v">{{ number_format((float) ($qtyBeli ?? 0), 4, ',', '.') }}</div></div>
                                            <div class="fund-pill"><div class="k">Harga</div><div class="v">{{ $item->formatted_unit_price }}</div></div>
                                            <div class="fund-pill"><div class="k">Nilai RAB</div><div class="v">{{ $item->formatted_total_price }}</div></div>
                                            <div class="fund-pill"><div class="k">Alokasi</div><div class="v">{{ 'Rp ' . number_format((float) ($jumlahAlokasi ?? 0), 0, ',', '.') }}</div></div>
                                            <div class="fund-pill"><div class="k">Selisih</div><div class="v {{ $selisih >= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ ($selisih >= 0 ? '+' : '-') . 'Rp ' . number_format(abs($selisih), 0, ',', '.') }}</div></div>
                                            <div class="fund-pill"><div class="k">Master</div><div class="v">{{ optional($primarySource)->master_kode ?? '-' }}</div></div>
                                            <div class="fund-pill" style="grid-column: span 2;"><div class="k">Catatan</div><div class="v">{{ $item->notes ?: '-' }}</div></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="category-total {{ $palette['subtotal'] }}">
                                Subtotal Kelompok {{ $category }}: 
                                <span class="font-mono font-semibold ml-2">
                                    Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Belum ada sub item RAB Breakdown</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Tambahkan sub item agar Work Breakdown Structure tampil seperti rincian pada file Excel.
                    </p>
                    @if($canEdit)
                        <a href="{{ route('dev.rab-breakdown.items.create', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Item Pertama
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg p-4 shadow border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20">
            <div class="text-sm font-medium text-sky-700 dark:text-sky-300 mb-1">Total Budget Items</div>
            <div class="text-2xl font-bold text-sky-900 dark:text-sky-100">
                Rp {{ number_format($rabBreakdown->items->sum('total_price'), 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-lg p-4 shadow border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20">
            <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300 mb-1">Total Items</div>
            <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                {{ $rabBreakdown->items->count() }}
            </div>
        </div>
        <div class="rounded-lg p-4 shadow border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20">
            <div class="text-sm font-medium text-violet-700 dark:text-violet-300 mb-1">Avg. Price per Item</div>
            <div class="text-2xl font-bold text-violet-900 dark:text-violet-100">
                @php
                    $avgPrice = $rabBreakdown->items->count() > 0 ? $rabBreakdown->items->avg('total_price') : 0;
                @endphp
                Rp {{ number_format($avgPrice, 0, ',', '.') }}
            </div>
        </div>
    </div>

    @include('dev.rab-breakdown.partials.footnote-terms')

</div>

<script>
    // Toggle category sections
    function toggleCategory(category) {
        const content = document.getElementById(`content-${category}`);
        const icon = document.getElementById(`icon-${category}`);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.style.display = 'none';
            icon.style.transform = 'rotate(-90deg)';
        }
    }
    
    // Initialize: show first category, hide others
    document.addEventListener('DOMContentLoaded', function() {
        const categories = document.querySelectorAll('.category-content');
        categories.forEach((content, index) => {
            if (index === 0) {
                content.style.display = 'block';
                const category = content.id.replace('content-', '');
                const icon = document.getElementById(`icon-${category}`);
                if (icon) icon.style.transform = 'rotate(0deg)';
            } else {
                content.style.display = 'none';
                const category = content.id.replace('content-', '');
                const icon = document.getElementById(`icon-${category}`);
                if (icon) icon.style.transform = 'rotate(-90deg)';
            }
        });
        
        // Animate progress bar
        const progressBar = document.querySelector('.progress-bar-fill');
        if (progressBar) {
            const width = progressBar.style.width;
            progressBar.style.width = '0';
            setTimeout(() => {
                progressBar.style.width = width;
            }, 300);
        }
    });
    
    // Export function
    function exportRabBreakdown() {
        // TODO: Implement export functionality
        alert('Export feature coming soon!');
    }
    
    // Edit item function
    function editItem(itemId) {
        // TODO: Implement quick edit or redirect to edit page
        window.location.href = `/dev/projects/{{ $project->id }}/rab-breakdown/{{ $rabBreakdown->id }}/items/${itemId}/edit`;
    }
    
    // Auto-submit progress form on actual amount change
    document.getElementById('progressForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        submitBtn.disabled = true;
    });

</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('{{ session('success') }}', 'success');
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('{{ session('error') }}', 'error');
    });
</script>
@endif
@endsection







