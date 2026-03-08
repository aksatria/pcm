@extends('layouts.dev')

@section('title', 'Rekap Voucher Pembelian - ' . $project->name)
@section('subtitle', 'Rekap Voucher Pembelian per RAPP')

@section('content')
@php
    $subtotalSum   = (float) ($totals->subtotal_sum ?? 0);
    $taxSum        = (float) ($totals->tax_sum ?? 0);
    $totalSum      = (float) ($totals->total_sum ?? 0);
    $totalVouchers = $vouchers->total();
    $isAdmin = auth()->check() && (auth()->user()->is_admin ?? false);
    $statusClass = function($status) {
        return match($status) {
            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
            'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
    };
@endphp

<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">Rekap Voucher Pembelian</h1>
            <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                Proyek: <span class="font-semibold">{{ $project->name }}</span>
                @if(!empty($project->code))
                    &bull; Kode: <span class="font-mono">{{ $project->code }}</span>
                @endif
                @if(!empty($rab->name))
                    &bull; RAPP: <span class="font-semibold">{{ $rab->name }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dev.projects.show', $project->id) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
                Kembali ke Project
            </a>
            <a href="{{ route('dev.rabs.purchase-vouchers.print-index', ['projectId' => $project->id, 'rabId' => $rab->id] + request()->query()) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
                Print
            </a>
            <a href="{{ route('dev.rabs.purchase-vouchers.pdf-index', ['projectId' => $project->id, 'rabId' => $rab->id] + request()->query()) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">
                PDF
            </a>
            <a href="{{ route('dev.rab-baseline.show', [$project->id, $rab->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
                Kembali ke RAPP
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['title' => 'Jumlah Voucher', 'value' => number_format($totalVouchers, 0, ',', '.'), 'description' => 'Total voucher pembelian', 'color' => 'blue'],
            ['title' => 'Total Subtotal', 'value' => 'Rp ' . number_format($subtotalSum, 0, ',', '.'), 'description' => 'Akumulasi sebelum PPN', 'color' => 'purple'],
            ['title' => 'Total PPN', 'value' => 'Rp ' . number_format($taxSum, 0, ',', '.'), 'description' => 'Total PPN seluruh voucher', 'color' => 'yellow'],
            ['title' => 'Total Pembayaran', 'value' => 'Rp ' . number_format($totalSum, 0, ',', '.'), 'description' => 'Subtotal + PPN', 'color' => 'green'],
        ] as $stat)
        <div class="group relative min-h-[120px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold break-words">{{ $stat['value'] }}</p>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-{{ $stat['color'] }}-100 text-xs">{{ $stat['description'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
        <form method="GET" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Cari Voucher</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                           placeholder="No voucher / vendor / invoice / tujuan">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor</label>
                    <input type="text" name="vendor" value="{{ $filters['vendor'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                           placeholder="Nama vendor">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Dari</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Sampai</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Filter berdasarkan tanggal, vendor, atau kata kunci.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dev.rab-baseline.purchase-vouchers.index', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                       class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Reset
                    </a>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Daftar Voucher Pembelian</h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Menampilkan {{ $vouchers->count() }} dari {{ $totalVouchers }} voucher.</p>
        </div>

        <div class="p-0">
            <table class="w-full border-t border-gray-200 dark:border-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr class="text-gray-600 dark:text-gray-300">
                        <th class="px-2 py-2 text-center w-10">No</th>
                        <th class="px-2 py-2 text-left">Voucher</th>
                        <th class="px-2 py-2 text-left">Vendor / Invoice</th>
                        <th class="px-2 py-2 text-left">Tujuan</th>
                        <th class="px-2 py-2 text-right">Total</th>
                        <th class="px-2 py-2 text-center w-24">Status</th>
                        <th class="px-2 py-2 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($vouchers as $voucher)
                        @php $itemCount = $voucher->items_count ?? ($voucher->items?->count() ?? 0); @endphp
                        <tr class="even:bg-gray-50/70 dark:even:bg-gray-900/40 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <td class="px-2 py-2 text-center text-gray-500">
                                {{ ($vouchers->currentPage() - 1) * $vouchers->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-2 py-2">
                                @php
                                    $srcLabel = $voucher->lpb_id
                                        ? 'LPB ' . ($voucher->lpb?->lpb_no ?? ('#'.$voucher->lpb_id))
                                        : 'PO ' . ($voucher->purchaseOrder?->po_no ?? ($voucher->purchase_order_id ? ('#'.$voucher->purchase_order_id) : '-'));
                                @endphp
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $voucher->voucher_no ?? '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $voucher->voucher_date ? $voucher->voucher_date->format('d M Y') : '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Sumber: {{ $srcLabel }}</div>
                            </td>
                            <td class="px-2 py-2">
                                <div class="text-sm text-gray-800 dark:text-gray-100">{{ $voucher->vendor_name ?: '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Invoice: {{ $voucher->invoice_no ?: '-' }}</div>
                            </td>
                            <td class="px-2 py-2">
                                <span class="block text-[12px] text-gray-700 dark:text-gray-200 max-w-xs">{{ $voucher->payment_purpose ?: '-' }}</span>
                            </td>
                            <td class="px-2 py-2 text-right">
                                <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 whitespace-nowrap">
                                    Rp {{ number_format($voucher->total_amount ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Sub: Rp {{ number_format($voucher->subtotal_amount ?? 0, 0, ',', '.') }} &bull; PPN: Rp {{ number_format($voucher->tax_amount ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $itemCount }} item
                                </div>
                            </td>
                            <td class="px-2 py-2 text-center">
                                @php $st = $voucher->status ?? 'draft'; @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statusClass($st) }}">{{ $st }}</span>
                                @if($st === 'rejected' && ($voucher->rejected_reason ?? null))
                                    <span class="ml-1 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200" title="{{ $voucher->rejected_reason }}">Alasan</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('dev.rabs.purchase-vouchers.show', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
                                       class="px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        Detail
                                    </a>
                                    @if($isAdmin)
                                        <a href="{{ route('dev.rabs.purchase-vouchers.edit', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
                                           class="px-2.5 py-1.5 rounded-lg border border-blue-300 dark:border-blue-600 text-[11px] text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/40">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada voucher pembelian untuk RAPP ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($vouchers->count())
                    @php
                        $pageSubtotal = $vouchers->sum('subtotal_amount');
                        $pageTax      = $vouchers->sum('tax_amount');
                        $pageTotal    = $vouchers->sum('total_amount');
                    @endphp
                    <tfoot class="bg-gray-100 dark:bg-slate-900 border-t-2 border-gray-300 dark:border-slate-700">
                        <tr>
                            <td colspan="4" class="px-2 py-2 text-right text-[11px] font-semibold text-gray-700 dark:text-gray-200">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-200 dark:bg-slate-800 text-gray-800 dark:text-gray-200 text-[11px] font-semibold">
                                    TOTAL HALAMAN INI
                                </span>
                            </td>
                            <td class="px-2 py-2 text-right text-[12px] font-bold text-emerald-700 dark:text-emerald-300">
                                Rp {{ number_format($pageTotal, 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-2 text-center text-[11px] text-gray-500 dark:text-gray-300">
                                Sub: Rp {{ number_format($pageSubtotal, 0, ',', '.') }} &bull; PPN: Rp {{ number_format($pageTax, 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-[11px]">
                <div class="text-gray-500 dark:text-gray-400">Halaman {{ $vouchers->currentPage() }} dari {{ $vouchers->lastPage() }}</div>
                <div>{{ $vouchers->links('vendor.pagination.tailwind') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection



