{{-- resources/views/dev/RAPPs/partials/items-table.blade.php --}}

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Item</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Satuan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Volume</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Satuan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
                @if($editable)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($items as $item)
                <tr id="item-row-{{ $item->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    {{-- Kode --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-mono text-gray-900 dark:text-white">
                            {{ $item->data->kode ?? 'N/A' }}
                        </div>
                    </td>

                    {{-- Nama item + deskripsi --}}
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $item->data->uraian ?? 'Data tidak ditemukan' }}
                        </div>
                        @if($item->data && $item->data->deskripsi)
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $item->data->deskripsi }}
                            </div>
                        @endif
                        @if(!$item->data)
                            <div class="text-xs text-red-500 dark:text-red-400 mt-1">
                                ? Data master tidak ditemukan
                            </div>
                        @endif
                    </td>

                    {{-- Kategori --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ $item->data->kategori ?? 'Unknown' }}
                        </span>
                    </td>

                    {{-- Satuan --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $item->satuan ?? '-' }}
                        </div>
                    </td>

                    {{-- Volume (editable kalau $editable) --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($editable)
                            <input type="number"
                                   id="volume-{{ $item->id }}"
                                   value="{{ $item->volume }}"
                                   min="0.0001"
                                   step="0.0001"
                                   class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white"
                                   onchange="updateItem({{ $item->id }}, 'volume')"
                                   onblur="updateItem({{ $item->id }}, 'volume')">
                        @else
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ number_format($item->volume, 4) }}
                            </div>
                        @endif
                    </td>

                    {{-- Harga Satuan (editable kalau $editable) --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($editable)
                            <input type="text"
                                   id="harga_satuan-{{ $item->id }}"
                                   value="{{ number_format($item->harga_satuan, 0, ',', '.') }}"
                                   class="w-32 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white"
                                   onchange="updateItem({{ $item->id }}, 'harga_satuan')"
                                   onblur="ensureNumericValue(this); formatCurrencyInput(this); updateItem({{ $item->id }}, 'harga_satuan')"
                                   oninput="formatCurrencyInput(this)"
                                   onfocus="removeCurrencyFormatting(this)"
                                   data-original-value="{{ $item->harga_satuan }}">
                        @else
                            <div class="text-sm text-gray-900 dark:text-white">
                                Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>

                    {{-- Subtotal --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div id="total_harga-{{ $item->id }}" class="text-sm font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format($item->total_harga, 0, ',', '.') }}
                        </div>
                    </td>

                    {{-- Aksi --}}
                    @if($editable)
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button type="button"
                                    onclick="deleteItem({{ $item->id }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                                    title="Hapus Item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $editable ? 8 : 7 }}" class="px-6 py-8 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Belum ada items</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                Tambahkan items dari data master untuk memulai RAPP
                            </p>
                            <button type="button"
                                    onclick="openAddItemsModal()"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Items Pertama
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($items->count() > 0)
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td colspan="{{ $editable ? 6 : 5 }}" class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                        Total RAPP:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div id="total-RAPP-amount" class="text-lg font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($items->sum('total_harga'), 0, ',', '.') }}
                        </div>
                    </td>
                    @if($editable)
                        <td></td>
                    @endif
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<script>
// --- FORMAT CURRENCY ---

function formatCurrencyInput(input) {
    const start = input.selectionStart;
    const end = input.selectionEnd;

    let value = input.value.replace(/[^\d]/g, '');

    if (value) {
        value = parseInt(value).toLocaleString('id-ID');
    } else {
        value = '0';
    }

    input.value = value;

    if (typeof input.setSelectionRange === 'function') {
        const length = input.value.length;
        input.setSelectionRange(length, length);
    }
}

function removeCurrencyFormatting(input) {
    const rawValue = input.value.replace(/[^\d]/g, '');
    input.value = rawValue;
}

function ensureNumericValue(input) {
    const rawValue = input.value.replace(/[^\d]/g, '');
    if (!rawValue || parseInt(rawValue) < 0) {
        input.value = '0';
        setTimeout(() => formatCurrencyInput(input), 10);
    }
}

function getNumericValue(currencyInput) {
    const value = currencyInput.value.replace(/[^\d]/g, '');
    return value ? parseInt(value) : 0;
}

function getOriginalValue(input) {
    return parseFloat(input.getAttribute('data-original-value')) || 0;
}

// --- UPDATE ITEM (VOLUME / HARGA SATUAN) ---

function updateItem(itemId, field) {
    const input = document.getElementById(`${field}-${itemId}`);
    if (!input) return;

    let value;

    if (field === 'volume') {
        value = parseFloat(input.value);

        if (isNaN(value) || value < 0.0001) {
            alert('Volume harus lebih besar dari 0.0001');
            input.value = input.defaultValue || getOriginalValue(input);
            return;
        }
    } else {
        value = getNumericValue(input);

        if (isNaN(value) || value < 0) {
            alert('Harga satuan tidak valid');
            input.value = input.defaultValue || getOriginalValue(input);
            setTimeout(() => formatCurrencyInput(input), 10);
            return;
        }
    }

    if (value === null || value === undefined || isNaN(value)) {
        alert('Nilai tidak valid');
        input.value = input.defaultValue || getOriginalValue(input);
        return;
    }

    input.disabled = true;

    const updateUrl = "{{ route('dev.rab-baseline.items.update', ['projectId' => $project->id, 'rabId' => $rab->id, 'itemId' => 'ITEM_ID']) }}"
        .replace('ITEM_ID', itemId);

    console.log('Updating item:', { itemId, field, value, url: updateUrl });

    fetch(updateUrl, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            field: field,
            value: value,
            _method: 'PUT'
        })
    })
    .then(response => {
        console.log('Response status:', response.status);

        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }).catch(() => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }

        return response.json();
    })
    .then(data => {
        console.log('Update success:', data);
        input.disabled = false;

        if (!data.success) {
            throw new Error(data.message || 'Gagal mengupdate item.');
        }

        // Update subtotal dari server kalau ada
        if (typeof data.subtotal !== 'undefined') {
            const subtotalEl = document.getElementById(`total_harga-${itemId}`);
            if (subtotalEl) {
                subtotalEl.textContent = 'Rp ' + parseFloat(data.subtotal).toLocaleString('id-ID');
            }
        } else {
            // fallback: hitung manual
            const volumeNow = field === 'volume'
                ? value
                : parseFloat(document.getElementById(`volume-${itemId}`).value);
            const hargaInput = document.getElementById(`harga_satuan-${itemId}`);
            const hargaNow = field === 'harga_satuan'
                ? value
                : getNumericValue(hargaInput);
            const total = volumeNow * hargaNow;

            const subtotalEl = document.getElementById(`total_harga-${itemId}`);
            if (subtotalEl) {
                subtotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }
        }

        // Update total RAPP dari server kalau ada
        if (typeof data.total_budget !== 'undefined') {
            const totalBudgetEl = document.getElementById('total-RAPP-amount');
            if (totalBudgetEl) {
                totalBudgetEl.textContent = 'Rp ' + parseFloat(data.total_budget).toLocaleString('id-ID');
            }
        }

        // Simpan default value baru
        input.defaultValue = (field === 'volume') ? value : input.value;
        if (field === 'harga_satuan') {
            input.setAttribute('data-original-value', value);
            setTimeout(() => formatCurrencyInput(input), 10);
        }

        showNotification('Item berhasil diperbarui', 'success');
    })
    .catch(error => {
        console.error('Update error:', error);
        input.disabled = false;

        let errorMessage = error.message || 'Terjadi kesalahan saat mengupdate item.';
        if (errorMessage.includes('404')) errorMessage = 'Item tidak ditemukan (404). Silakan refresh halaman.';
        if (errorMessage.includes('403')) errorMessage = 'RAPP tidak dapat diedit (status tidak mengizinkan).';

        alert(errorMessage);
        input.value = input.defaultValue || getOriginalValue(input);
        if (field === 'harga_satuan') {
            setTimeout(() => formatCurrencyInput(input), 10);
        }
    });
}

// --- DELETE ITEM ---

function deleteItem(itemId) {
    if (!confirm('Hapus item ini dari RAPP?')) {
        return;
    }

    const url = "{{ route('dev.rab-baseline.items.destroy', ['projectId' => $project->id, 'rabId' => $rab->id, 'itemId' => 'ITEM_ID']) }}"
        .replace('ITEM_ID', itemId);

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json, text/html',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async response => {
        // Controller destroyItem sekarang balas redirect (HTML), bukan JSON.
        // Jadi kita handle 2 kemungkinan: JSON atau HTML.
        let data = null;
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            data = await response.json();
        }

        if (!response.ok) {
            const msg = data && data.message ? data.message : `HTTP error! status: ${response.status}`;
            throw new Error(msg);
        }

        // Kalau JSON valid dengan success=true
        if (data && data.success) {
            const row = document.getElementById('item-row-' + itemId);
            if (row) row.remove();
            showNotification('Item berhasil dihapus', 'success');
            setTimeout(() => location.reload(), 800);
            return;
        }

        // Kalau bukan JSON (redirect HTML), tetap reload saja
        const row = document.getElementById('item-row-' + itemId);
        if (row) row.remove();
        showNotification('Item berhasil dihapus', 'success');
        setTimeout(() => location.reload(), 800);
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert(error.message || 'Terjadi kesalahan saat menghapus item.');
    });
}

// --- NOTIFICATION ---

function showNotification(message, type = 'success') {
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className =
        `custom-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-fade-in ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;

    notification.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="${type === 'success' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'}"/>
        </svg>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// --- INIT DEFAULT VALUES ---

document.addEventListener('DOMContentLoaded', function () {
    const volumeInputs = document.querySelectorAll('input[id^="volume-"]');
    volumeInputs.forEach(input => {
        input.defaultValue = input.value;
    });

    const hargaInputs = document.querySelectorAll('input[id^="harga_satuan-"]');
    hargaInputs.forEach(input => {
        input.defaultValue = input.value;
        formatCurrencyInput(input);
    });
});

// --- DEBUG HELPER (OPSIONAL) ---

function debugItemValues(itemId) {
    const volumeInput = document.getElementById(`volume-${itemId}`);
    const hargaInput = document.getElementById(`harga_satuan-${itemId}`);

    console.log('Debug Item Values:', {
        itemId: itemId,
        volume: {
            display: volumeInput ? volumeInput.value : null,
            numeric: volumeInput ? parseFloat(volumeInput.value) : null,
            original: volumeInput ? getOriginalValue(volumeInput) : null
        },
        harga_satuan: {
            display: hargaInput ? hargaInput.value : null,
            numeric: hargaInput ? getNumericValue(hargaInput) : null,
            original: hargaInput ? getOriginalValue(hargaInput) : null
        }
    });
}
</script>

<style>
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

input:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>







