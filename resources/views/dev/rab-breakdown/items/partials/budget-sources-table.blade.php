@if($budgetSources->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Kode Master
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Kategori
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Uraian
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Volume
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Harga Satuan
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Catatan
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($budgetSources as $source)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium 
                                {{ str_starts_with($source->master_kategori, 'MT') ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 
                                   (str_starts_with($source->master_kategori, 'JS') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' :
                                   (str_starts_with($source->master_kategori, 'AT') ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' :
                                   (str_starts_with($source->master_kategori, 'SR') ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' :
                                   'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'))) }}">
                                {{ $source->master_kode }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $source->master_kategori }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ \Illuminate\Support\Str::limit($source->master_uraian, 60) }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($source->allocated_volume, 2) }} {{ $source->master_satuan }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-mono">
                            Rp {{ number_format($source->master_harga, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right font-mono">
                            Rp {{ number_format($source->allocated_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $source->notes ?? '-' }}
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                        Total Budget Sources:
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right font-mono">
                        Rp {{ number_format($budgetSources->sum('allocated_amount'), 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="text-center py-8">
        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada sumber budget</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Item belum di-mapping ke Master Data.
        </p>
    </div>
@endif

