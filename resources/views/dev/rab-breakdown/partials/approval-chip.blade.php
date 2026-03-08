@php
    $approvalValue = strtolower((string) ($approvalKey ?? 'draft'));
    $approvalClass = in_array($approvalValue, ['approved', 'submitted', 'rejected', 'draft'], true)
        ? $approvalValue
        : 'draft';
    $approvalLabel = strtoupper($approvalValue);
    $prefixLabel = isset($prefix) ? (string) $prefix : 'APP';
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold border
    @if($approvalClass === 'approved') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800
    @elseif($approvalClass === 'submitted') bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/20 dark:text-indigo-300 dark:border-indigo-800
    @elseif($approvalClass === 'rejected') bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-800
    @else bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/30 dark:text-slate-300 dark:border-slate-700
    @endif">
    {{ $prefixLabel }}: {{ $approvalLabel }}
</span>
