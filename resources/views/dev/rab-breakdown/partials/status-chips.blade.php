<div class="mb-2">
    @include('dev.rab-breakdown.partials.approval-chip', ['approvalKey' => $approvalKey, 'prefix' => 'APP'])

    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold border
        {{ ($canEdit ?? false)
            ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800'
            : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800' }}">
        {{ ($canEdit ?? false) ? 'EDIT MODE: OPEN' : 'EDIT MODE: LOCKED' }}
    </span>
</div>
