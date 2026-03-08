{{-- resources/views/dev/rapp/partials/rapp-item.blade.php --}}
<div class="rapp-item border border-gray-200 dark:border-gray-600 rounded-lg p-4">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-3 mb-2">
                <span class="job-code inline-flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-bold rounded-full flex-shrink-0">
                    {{ $item->job_code }}
                </span>
                <div class="flex-1 min-w-0">
                    <h4 class="text-base font-medium text-gray-900 dark:text-white break-words">
                        {{ $item->description }}
                    </h4>
                    @if($item->unit || $item->volume > 0 || $item->unit_cost_estimate > 0)
                    <div class="flex items-center space-x-4 mt-1 text-sm text-gray-500 dark:text-gray-400 flex-wrap gap-2">
                        @if($item->unit)
                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                            </svg>
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
            </div>
        </div>
        
        <div class="flex items-center space-x-4 ml-4 flex-shrink-0">
            <div class="text-right">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $item->formatted_total_estimate }}
                </div>
                @if($item->volume > 0 && $item->unit_cost_estimate > 0 && !$item->has_children)
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $item->formatted_volume }} × {{ $item->formatted_unit_cost_estimate }}
                </div>
                @endif
            </div>
            
            @if($rapp->canEdit() && $item->rapp_id == $rapp->id)
            <div class="flex items-center space-x-1">
                <button type="button" onclick="openEditItemModal('{{ $item->id }}')"
                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1 rounded transition-colors"
                        title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                
                <button type="button" onclick="addChildItem('{{ $item->job_code }}')"
                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-1 rounded transition-colors"
                        title="Tambah Sub-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                
                {{-- ✅ PERBAIKAN: Form biasa, bukan AJAX --}}
                <form action="{{ route('dev.rapp.destroy-item', [$rapp, $item]) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1 rounded transition-colors"
                            title="Hapus"
                            onclick="return confirm('Hapus item {{ $item->job_code }}?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    @if($item->children && $item->children->count() > 0)
        <div class="rapp-children mt-3 space-y-2">
            @foreach($item->children as $child)
                @include('dev.rapp.partials.rapp-item', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

<script>
function addChildItem(parentCode) {
    document.getElementById('parent_code').value = parentCode;
    openAddItemModal();
}

function openEditItemModal(itemId) {
    // Implement edit modal similar to add modal
    // You can fetch item data via AJAX and populate the form
    alert('Edit functionality for item ' + itemId + ' akan diimplementasikan');
}
</script>
