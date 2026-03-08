@extends('layouts.dev')

@section('title', 'Detail Voucher Pembelian - ' . ($voucher->voucher_no ?? ''))
@section('subtitle', 'Detail Voucher Pembelian')

@section('content')
@php
    $itemCount = $voucher->items?->count() ?? 0;
    $subtotal  = (float) ($voucher->subtotal_amount ?? 0);
    $tax       = (float) ($voucher->tax_amount ?? 0);
    $total     = (float) ($voucher->total_amount ?? 0);
    $fmtDate   = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $status    = $voucher->status ?? 'draft';
    $statusLabel = $status === 'cancelled' ? 'dibatalkan' : $status;
    $sourceLabel = $voucher->lpb_id
        ? 'LPB ' . ($voucher->lpb?->lpb_no ?? ('#'.$voucher->lpb_id))
        : 'PO ' . ($voucher->purchaseOrder?->po_no ?? ($voucher->purchase_order_id ? ('#'.$voucher->purchase_order_id) : '-'));
    $isHO = auth()->check() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
    $locked = in_array(strtolower($status), ['submitted', 'approved', 'closed', 'final', 'cancelled'], true);
    $canEdit = $isHO || !$locked;
    $statusClass = match($status) {
        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800/60 dark:text-gray-200',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
@endphp

<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Detail Voucher Pembelian</h1>
            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $project->name }}</span>
                @if(!empty($project->code))
                    &nbsp;- Kode: <span class="font-mono">{{ $project->code }}</span>
                @endif
                @if(!empty($rab->name))
                    &nbsp;- RAPP: <span class="font-semibold">{{ $rab->name }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
            @if(in_array($status, ['draft', 'rejected']))
                <form method="POST" action="{{ route('dev.rabs.purchase-vouchers.submit', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Submit</button>
                </form>
            @endif
            @if($status === 'submitted' && $isHO)
                <form method="POST" action="{{ route('dev.rabs.purchase-vouchers.approve', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Approve</button>
                </form>
                <label for="rejectVoucherModal" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium cursor-pointer">Reject</label>
            @endif
            <a href="{{ route('dev.rab-baseline.purchase-vouchers.index', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Kembali ke Rekap</a>
            @if($canEdit)
                <a href="{{ route('dev.rabs.purchase-vouchers.edit', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Edit</a>
            @endif
            <a href="{{ route('dev.rabs.purchase-vouchers.print', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Print</a>
            <a href="{{ route('dev.rabs.purchase-vouchers.pdf', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">PDF</a>
        </div>
    </div>

    @if(!$canEdit)
        <div class="rounded-xl border border-blue-200 dark:border-blue-900/40 bg-blue-50/80 dark:bg-blue-900/20 p-4 text-sm text-blue-800 dark:text-blue-200">
            Dokumen sudah {{ $statusLabel }} dan tidak bisa diubah.
        </div>
    @endif

    @if($status === 'rejected' && ($voucher->rejected_reason ?? null))
        <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
            <div class="font-semibold mb-1">Alasan Reject</div>
            <div class="text-xs leading-relaxed">{{ $voucher->rejected_reason }}</div>
        </div>
    @endif

    @if($status === 'submitted' && $isHO)
        <input id="rejectVoucherModal" type="checkbox" class="peer hidden" />
        <div class="fixed inset-0 hidden peer-checked:flex items-center justify-center z-50">
            <label for="rejectVoucherModal" class="absolute inset-0 bg-black/40"></label>
            <form method="POST" action="{{ route('dev.rabs.purchase-vouchers.reject', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}" class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
                @csrf
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject Voucher</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
                <textarea name="rejected_reason" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 px-3 py-2" placeholder="Tulis alasan reject..."></textarea>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <label for="rejectVoucherModal" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">Batal</label>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium">Reject</button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 space-y-3">
            <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ringkasan Voucher</div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Nomor Voucher</div>
                <div class="font-semibold">{{ $voucher->voucher_no ?? '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Tanggal</div>
                <div class="font-medium">{{ $fmtDate($voucher->voucher_date ?? null) }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Jumlah Item</div>
                <div class="font-medium">{{ $itemCount }} item</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Sumber</div>
                <div class="font-medium">{{ $sourceLabel }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 space-y-3">
            <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vendor dan Dokumen</div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Vendor</div>
                <div class="font-semibold">{{ $voucher->vendor_name ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">No Invoice / Nota</div>
                <div class="font-medium">{{ $voucher->invoice_no ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Metode Pembayaran</div>
                <div class="font-medium">{{ $voucher->payment_method ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Jatuh Tempo</div>
                <div class="font-medium">{{ $fmtDate($voucher->due_date ?? null) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 space-y-3">
            <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Rekening dan Tujuan</div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Rekening</div>
                <div class="font-medium">{{ $voucher->bank ?: '-' }} / {{ $voucher->account_no ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Atas Nama</div>
                <div class="font-medium">{{ $voucher->account_name ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Tujuan Pembayaran</div>
                <div class="font-medium">{{ $voucher->payment_purpose ?: '-' }}</div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Catatan</div>
                <div class="font-medium">{{ $voucher->notes ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Ringkasan Pembayaran</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Realisasi RAPP mengikuti rumus: <span class="font-semibold">Qty x Harga Satuan RAPP</span>.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg p-3">
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Subtotal</div>
                    <div class="font-semibold text-gray-900 dark:text-gray-50">Rp {{ number_format($subtotal, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">PPN ({{ number_format($voucher->tax_percent ?? 0, 2, ',', '.') }}%)</div>
                    <div class="font-semibold text-gray-900 dark:text-gray-50">Rp {{ number_format($tax, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Ongkir</div>
                    <div class="font-semibold text-gray-900 dark:text-gray-50">Rp {{ number_format((float) ($voucher->shipping_cost ?? 0), 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Total</div>
                    <div class="font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($total, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-900/40 px-4 py-3">
            <div class="text-sm text-emerald-700 dark:text-emerald-200 font-semibold">Total Pembayaran</div>
            <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($total, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Detail Item Voucher</h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $itemCount }} baris item</p>
        </div>

        <div class="p-0">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                        <th class="px-2 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">No</th>
                        <th class="px-2 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Item</th>
                        <th class="px-2 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Qty</th>
                        <th class="px-2 py-2 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Harga</th>
                        <th class="px-2 py-2 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Realisasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($voucher->items as $item)
                        @php
                            $rabItem   = $item->rabItem;
                            $data      = $rabItem?->data;
                            $kode      = $data->kode ?? $data->item_code ?? '';
                            $uraian    = $data->uraian ?? $data->item_name ?? '';
                            $satuan    = $data->satuan ?? $rabItem->satuan ?? $item->unit ?? '';

                            $qty       = (float) ($item->qty ?? 0);
                            $priceNota = (float) ($item->price ?? 0);
                            $amount    = (float) ($item->amount ?? ($qty * $priceNota));

                            $hsRapp    = (float) ($item->rab_harga_satuan_snapshot ?? $rabItem->harga_satuan ?? 0);
                            $realisasiExcel = $qty * $hsRapp;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <td class="px-2 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-2 py-2">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $uraian ?: '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $kode ?: '-' }} - {{ $satuan ?: '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">RAPP: Rp {{ number_format($hsRapp, 0, ',', '.') }} / {{ number_format($rabItem?->volume ?? 0, 2, ',', '.') }}</div>
                            </td>
                            <td class="px-2 py-2 text-center text-gray-900 dark:text-gray-50 whitespace-nowrap">{{ number_format($qty, 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right text-gray-900 dark:text-gray-50">
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Nota</div>
                                <div class="font-semibold">Rp {{ number_format($amount, 0, ',', '.') }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">({{ number_format($priceNota, 0, ',', '.') }}/{{ $satuan ?: '-' }})</div>
                            </td>
                            <td class="px-2 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($realisasiExcel, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada item pada voucher ini.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if($itemCount)
                    @php
                        $sumQty         = $voucher->items->sum('qty');
                        $sumAmountNota  = $voucher->items->sum('amount');
                        $sumRealisasi   = $voucher->items->sum(function ($it) {
                            $qty = (float) $it->qty;
                            $hs  = (float) ($it->rab_harga_satuan_snapshot ?? $it->rabItem->harga_satuan ?? 0);
                            return $qty * $hs;
                        });
                    @endphp
                    <tfoot class="bg-gray-100 dark:bg-slate-900 border-t-2 border-gray-300 dark:border-slate-700">
                        <tr>
                            <td colspan="2" class="px-2 py-2 text-right text-[11px] font-semibold text-gray-700 dark:text-gray-200">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-200 dark:bg-slate-800 text-gray-800 dark:text-gray-200 text-[10px] font-semibold">TOTAL ITEM</span>
                            </td>
                            <td class="px-2 py-2 text-center font-semibold text-gray-900 dark:text-gray-50">{{ number_format($sumQty, 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right font-semibold text-gray-900 dark:text-gray-50">Rp {{ number_format($sumAmountNota, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($sumRealisasi, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection



