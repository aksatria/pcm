@extends('layouts.dev')

@section('title', 'Summary RAPP - ' . $project->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Summary RAPP</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $project->code }}
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
                <p class="text-lg font-semibold text-green-600 dark:text-green-400 mt-2">
                    Total RAPP: Rp {{ number_format($grandTotal, 0, ',', '.') }}
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('dev.rab-baseline.index', $project) }}" 
                   class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke RAPP
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribusi Biaya per Kategori</h3>
        
        <div class="space-y-4">
            @foreach($summary as $category => $total)
            @if($grandTotal > 0)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ \App\Models\RabItem::getCategoryLabelStatic($category) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        Rp {{ number_format($total, 0, ',', '.') }} 
                        ({{ number_format(($total / $grandTotal) * 100, 1) }}%)
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" 
                         style="width: {{ ($total / $grandTotal) * 100 }}%"></div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>

    <!-- Summary Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rincian per Kategori</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Kategori
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total Biaya
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Persentase
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($summary as $category => $total)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ \App\Models\RabItem::getCategoryLabelStatic($category) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm text-gray-900 dark:text-white">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $grandTotal > 0 ? number_format(($total / $grandTotal) * 100, 2) : 0 }}%
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-gray-50 dark:bg-gray-700 font-semibold">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">GRAND TOTAL</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-lg text-green-600 dark:text-green-400">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm text-gray-900 dark:text-white">100%</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection





