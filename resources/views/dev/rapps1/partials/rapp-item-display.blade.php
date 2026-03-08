{{-- resources/views/dev/rapp/partials/rapp-item-display.blade.php --}}
<div class="rapp-item-display border border-gray-200 dark:border-gray-600 rounded p-3">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2 mb-1">
                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-bold rounded flex-shrink-0">
                    {{ $item->job_code }}
                </span>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white break-words">
                    {{ $item->description }}
                </h4>
            </div>
            @if($item->unit || $item->volume > 0 || $item->unit_cost_estimate > 0)
            <div class="flex items-center space-x-3 text-xs text-gray-500 dark:text-gray-400 ml-8 flex-wrap gap-2">
                @if($item->unit)
                <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                    {{ $item->unit }}
                </span>
                @endif
                @if($item->volume > 0)
                <span>Volume: {{ $item->formatted_volume }}</span>
                @endif
                @if($item->unit_cost_estimate > 0 && !$item->has_children)
                <span>Harga: {{ $item->formatted_unit_cost_estimate }}</span>
                @endif
            </div>
            @endif
        </div>
        
        <div class="text-right flex-shrink-0 ml-4">
            <div class="text-sm font-bold text-gray-900 dark:text-white">
                {{ $item->formatted_total_estimate }}
            </div>
            @if($item->volume > 0 && $item->unit_cost_estimate > 0 && !$item->has_children)
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $item->formatted_volume }} × {{ $item->formatted_unit_cost_estimate }}
            </div>
            @endif
        </div>
    </div>

    @if($item->children && $item->children->count() > 0)
        <div class="rapp-children mt-2 ml-6 space-y-2 border-l-2 border-gray-200 dark:border-gray-600 pl-3">
            @foreach($item->children as $child)
                @include('dev.rapp.partials.rapp-item-display', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

<style>
.rapp-item-display {
    transition: all 0.2s ease;
}

.rapp-item-display:hover {
    background-color: #f8fafc;
}

.dark .rapp-item-display:hover {
    background-color: #374151;
}
</style>
