{{-- resources/views/dev/RAPPs/partials/category-table.blade.php --}}

@php
    use Illuminate\Support\Str;
@endphp

@if($itemsByCategory->isEmpty())
    <div class="mt-6">
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center">
            <div class="flex flex-col items-center justify-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Belum ada items</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md text-sm">
                    Tambahkan items dari data master untuk memulai RAPP. Items akan otomatis dikelompokkan berdasarkan kategori.
                </p>
                @if($editable ?? false)
                    <button type="button" onclick="openAddItemsModal()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Items Pertama
                    </button>
                @endif
            </div>
        </div>
    </div>
@else
    @foreach($itemsByCategory as $category => $items)
        @php
            $slug = Str::slug($category ?: 'uncategorized');
            $totalKategori = $items->sum('total_harga');
            $totalRab = $rab->total_budget ?: 0;
            $persenKategori = $totalRab > 0 ? ($totalKategori / $totalRab) * 100 : 0;
        @endphp

        <div class="mb-8 category-section" id="category-{{ $slug }}">
            <!-- Header Kategori -->
            <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7h18M3 12h18M3 17h18"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $category ?: 'Tanpa Kategori' }}
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                         bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                                {{ $items->count() }} item
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Total kategori: <span class="font-semibold">Rp {{ number_format($totalKategori, 0, ',', '.') }}</span>
                            @if($totalRab > 0)
                                â€¢ {{ number_format($persenKategori, 1, ',', '.') }}% dari total RAPP
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-300">
                        <label class="inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox"
                                   class="select-all-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600"
                                   onchange="toggleSelectAllCategory('{{ $slug }}', this)">
                            <span class="ml-2">Pilih semua</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tabel Items per Kategori -->
            <div class="overflow-x-auto bg-white dark:bg-gray-900/40 rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/80">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <span class="sr-only">Pilih</span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Kode
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Nama Item
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Satuan
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Volume
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Harga Satuan
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Subtotal
                            </th>
                            @if($editable ?? false)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Aksi
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($items as $item)
                        @php
                            /** @var \App\Models\RabItem $item */
                            $dataItem   = $item->data;
                            $kode       = $dataItem->kode ?? 'N/A';
                            $nama       = $dataItem->uraian ?? 'Data tidak ditemukan';
                            $satuan     = $item->satuan ?? ($dataItem->satuan ?? '-');
                            $vol        = (float) ($item->volume ?? 0);
                            $hs         = (float) ($item->harga_satuan ?? 0);
                            $subtotal   = $item->total_harga;
                            $sisaVol    = $item->sisa_volume;   // accessor di model
                            $sisaAmt    = $item->sisa_amount;   // accessor di model
                        @endphp

                        <tr id="item-row-{{ $item->id }}"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/70 transition-colors"
                            data-item-id="{{ $item->id }}"
                            data-item-name="{{ $nama }}"
                            data-item-satuan="{{ $satuan }}"
                            data-item-sisa-volume="{{ number_format($sisaVol, 4, '.', '') }}"
                            data-item-default-price="{{ number_format($hs, 2, '.', '') }}"
                            data-item-code="{{ $kode }}"
                            data-item-budget-amount="{{ number_format($subtotal, 2, '.', '') }}"
                            data-item-sisa-amount="{{ number_format($sisaAmt, 2, '.', '') }}">
                            <!-- Checkbox pilih untuk voucher -->
                            <td class="px-4 py-3 text-center align-top">
                                <input type="checkbox"
                                       class="row-item-checkbox category-{{ $slug }} h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600"
                                       value="{{ $item->id }}"
                                       onchange="onRowCheckboxChanged()">
                            </td>

                            <!-- Kode -->
                            <td class="px-4 py-3 whitespace-nowrap align-top">
                                <div class="text-sm font-mono text-gray-900 dark:text-gray-100">
                                    {{ $kode }}
                                </div>
                                <div class="text-[11px] text-gray-400 dark:text-gray-500">
                                    ID: {{ $item->id }}
                                </div>
                            </td>

                            <!-- Nama item -->
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $nama }}
                                </div>
                                @if($dataItem && $dataItem->deskripsi)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                        {{ Str::limit($dataItem->deskripsi, 80) }}
                                    </div>
                                @endif
                                @unless($dataItem)
                                    <div class="mt-1 text-xs text-red-500 dark:text-red-400">
                                        ? Data master tidak ditemukan
                                    </div>
                                @endunless
                            </td>

                            <!-- Satuan -->
                            <td class="px-4 py-3 whitespace-nowrap align-top">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $satuan }}
                                </div>
                            </td>

                            <!-- Volume -->
                            <td class="px-4 py-3 whitespace-nowrap text-right align-top">
                                @if($editable ?? false)
                                    <input type="number"
                                           id="volume-{{ $item->id }}"
                                           value="{{ number_format($vol, 4, '.', '') }}"
                                           min="0.0001"
                                           step="0.0001"
                                           class="w-24 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white text-right"
                                           onchange="updateItem({{ $item->id }}, 'volume')"
                                           onblur="updateItem({{ $item->id }}, 'volume')">
                                @else
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ number_format($vol, 4, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Harga satuan -->
                            <td class="px-4 py-3 whitespace-nowrap text-right align-top">
                                @if($editable ?? false)
                                    <input type="text"
                                           id="harga_satuan-{{ $item->id }}"
                                           value="{{ number_format($hs, 0, ',', '.') }}"
                                           class="w-28 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white text-right"
                                           onchange="updateItem({{ $item->id }}, 'harga_satuan')"
                                           onblur="ensureNumericValue(this); formatCurrencyInput(this); updateItem({{ $item->id }}, 'harga_satuan')"
                                           oninput="formatCurrencyInput(this)"
                                           onfocus="removeCurrencyFormatting(this)"
                                           data-original-value="{{ $hs }}">
                                @else
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($hs, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Subtotal -->
                            <td class="px-4 py-3 whitespace-nowrap text-right align-top">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                </div>
                            </td>

                            @if($editable ?? false)
                                <!-- Aksi -->
                                <td class="px-4 py-3 whitespace-nowrap text-left align-top">
                                    <div class="flex items-center space-x-2">
                                        <button type="button"
                                                onclick="editItem({{ $item->id }})"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Edit
                                        </button>
                                        <button type="button"
                                                onclick="deleteItem({{ $item->id }})"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/60">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>

                    <!-- Footer total kategori -->
                    <tfoot class="bg-gray-50 dark:bg-gray-800/80">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                Total kategori {{ $category ?: 'Tanpa Kategori' }}
                            </td>
                            <td colspan="{{ ($editable ?? false) ? 2 : 1 }}" class="px-4 py-3 text-right">
                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($totalKategori, 0, ',', '.') }}
                                </span>
                            </td>
                            @if($editable ?? false)
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach
@endif






