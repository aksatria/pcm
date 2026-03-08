@if($items->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Kode
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Uraian
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Volume
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Satuan
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Harga Satuan
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $item)
                    @php
                        $satuanRaw = strtoupper(trim((string) ($item->satuan ?? '')));
                        $isLs = in_array($satuanRaw, ['LS', 'LUMP SUM', 'LUMPSUM'], true);
                        $maxQty = $isLs
                            ? max((float) ($item->volume_rab ?? 0), 1)
                            : ((float) ($item->volume_rab ?? 0) * 1.20);
                        $maxJumlah = (float) ($item->total_price ?? 0) * ($isLs ? 1.05 : 1.25);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ $item->item_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ \Illuminate\Support\Str::limit($item->uraian, 60) }}
                            </div>
                            @if($item->notes)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ \Illuminate\Support\Str::limit($item->notes, 40) }}
                                </div>
                            @endif
                            <div class="mt-2 inline-flex items-center px-2 py-1 rounded text-[11px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                Maks Alokasi: Qty <= {{ number_format($maxQty, 2, ',', '.') }} {{ $item->satuan }}, Jumlah <= Rp {{ number_format($maxJumlah, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($item->volume_rab, 2) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $item->satuan }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-mono">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right font-mono">
                            Rp {{ number_format($item->total_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('dev.rab-breakdown.items.edit', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdownId, 'item' => $item->id]) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                   title="Edit Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('dev.rab-breakdown.items.destroy', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdownId, 'item' => $item->id]) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Hapus item {{ $item->item_code }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Hapus Item">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                        Total:
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right font-mono">
                        Rp {{ number_format($items->sum('total_price'), 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada items</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Mulai dengan menambahkan item pertama.
        </p>
        <div class="mt-6">
            <a href="{{ route('dev.rab-breakdown.items.create', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdownId]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Item
            </a>
        </div>
    </div>
@endif


