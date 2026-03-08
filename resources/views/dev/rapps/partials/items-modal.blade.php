<!-- resources/views/dev/RAPPs/partials/items-modal.blade.php -->

<!-- Add Items Modal -->
<div id="add-items-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAddItemsModal()"></div>

        <!-- Modal panel -->
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
            <!-- Close button -->
            <button type="button" onclick="closeAddItemsModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Modal content -->
            <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-4">
                        Pilih Items dari Data Master (Tersedia: {{ $availableItems->count() }} items)
                    </h3>

                    <!-- PERBAIKAN: Info status RAPP -->
                    @if($rab->status == 'rejected')
                    <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            RAPP ini dalam status <span class="font-bold">Rejected</span>. Anda dapat menambahkan items baru untuk memperbaiki RAPP.
                        </p>
                    </div>
                    @endif

                    <!-- Search and Filters -->
                    <div class="mb-6 space-y-4 sm:space-y-0 sm:flex sm:space-x-4 sm:items-center">
                        <!-- Search -->
                        <div class="flex-1">
                            <input type="text" 
                                   id="search-items"
                                   placeholder="Cari items berdasarkan kode atau uraian..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                   onkeyup="searchItems()">
                        </div>
                        
                        <!-- Category Filter -->
                        <select id="category-filter" onchange="filterByCategory()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Available Items Table -->
                    <div class="max-h-96 overflow-y-auto mb-6 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="available-items-table">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                                        <input type="checkbox" id="select-all-items" onchange="selectAllItems()">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uraian</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Satuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($availableItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" value="{{ $item->id }}" class="item-checkbox">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-900 dark:text-white">{{ $item->kode }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->uraian }}</div>
                                        @if($item->deskripsi)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->deskripsi }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ $item->kategori }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $item->satuan ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            @if($item->harga)
                                                Rp {{ number_format($item->harga, 0, ',', '.') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center">
                                        <div class="max-w-md mx-auto">
                                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tidak ada items tersedia</h3>
                                            <p class="text-gray-500 dark:text-gray-400">Semua items dari data master sudah ditambahkan ke RAPP ini.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Selected items counter -->
                    <div id="selected-count" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        0 items terpilih
                    </div>

                    <!-- Action buttons -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddItemsModal()"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Batal
                        </button>
                        <button type="button" onclick="addSelectedItems()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
                                id="add-items-button" disabled>
                            Tambah Items Terpilih
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Select All Items in Modal
function selectAllItems() {
    const checkboxes = document.querySelectorAll('#available-items-table .item-checkbox');
    const selectAll = document.getElementById('select-all-items');
    const visibleRows = Array.from(document.querySelectorAll('#available-items-table tbody tr')).filter(row => row.style.display !== 'none');
    
    visibleRows.forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        if (checkbox) {
            checkbox.checked = selectAll.checked;
        }
    });
    
    updateSelectedCount();
}

// Update selected items count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('#available-items-table .item-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selected-count').textContent = count + ' items terpilih';
    
    // Enable/disable add button
    const addButton = document.getElementById('add-items-button');
    addButton.disabled = count === 0;
}

// Search in modal
function searchItems() {
    const searchTerm = document.getElementById('search-items').value.toLowerCase();
    const rows = document.querySelectorAll('#available-items-table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const kode = row.cells[1].textContent.toLowerCase();
        const uraian = row.cells[2].textContent.toLowerCase();
        const kategori = row.cells[3].textContent.toLowerCase();
        
        if (kode.includes(searchTerm) || uraian.includes(searchTerm) || kategori.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update select all checkbox
    const selectAll = document.getElementById('select-all-items');
    selectAll.checked = false;
    updateSelectedCount();
}

// Filter by category
function filterByCategory() {
    const category = document.getElementById('category-filter').value;
    const rows = document.querySelectorAll('#available-items-table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const rowCategory = row.cells[3].textContent.trim();
        if (!category || rowCategory === category) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update select all checkbox
    const selectAll = document.getElementById('select-all-items');
    selectAll.checked = false;
    updateSelectedCount();
}

// Add selected items to RAPP
function addSelectedItems() {
    const checkboxes = document.querySelectorAll('#available-items-table .item-checkbox:checked');
    const itemIds = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    if (itemIds.length === 0) {
        alert('Pilih minimal satu item untuk ditambahkan.');
        return;
    }

    const addButton = document.getElementById('add-items-button');
    addButton.disabled = true;
    addButton.textContent = 'Menambahkan...';

    // Show loading state
    const originalText = addButton.innerHTML;
    addButton.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Menambahkan...';

    fetch("{{ route('dev.rab-baseline.items.bulk.store', ['projectId' => $project->id, 'rabId' => $rab->id]) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            item_ids: itemIds
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Items berhasil ditambahkan', 'success');
            closeAddItemsModal();
            // Reload halaman untuk menampilkan items baru
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Gagal menambahkan items');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Gagal menambahkan items: ' + error.message, 'error');
        addButton.disabled = false;
        addButton.textContent = 'Tambah Items Terpilih';
    });
}

// Add event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
});

// Initialize selected count when modal opens
function openAddItemsModal() {
    const modal = document.getElementById('add-items-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // Reset search and filter
    document.getElementById('search-items').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('select-all-items').checked = false;
    
    // Show all rows
    const rows = document.querySelectorAll('#available-items-table tbody tr');
    rows.forEach(row => row.style.display = '');
    
    updateSelectedCount();
}

// Show notification
function showNotification(message, type = 'success') {
    // Hapus notifikasi sebelumnya
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `custom-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-fade-in ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${
                type === 'success' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'
            }"/>
        </svg>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Close modal
function closeAddItemsModal() {
    const modal = document.getElementById('add-items-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}
</script>

<style>
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

#add-items-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>





