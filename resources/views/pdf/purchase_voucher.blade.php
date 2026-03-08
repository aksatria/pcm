@extends('pdf.layout')

@section('title', 'Voucher Pembayaran')
@section('header_title', 'VOUCHER PEMBAYARAN')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $subtotal = (float) ($voucher->subtotal_amount ?? 0);
    $tax = (float) ($voucher->tax_amount ?? 0);
    $total = (float) ($voucher->total_amount ?? 0);
    $sourceLabel = $voucher->lpb_id
        ? 'LPB ' . ($voucher->lpb?->lpb_no ?? ('#'.$voucher->lpb_id))
        : 'PO ' . ($voucher->purchaseOrder?->po_no ?? ($voucher->purchase_order_id ? ('#'.$voucher->purchase_order_id) : '-'));
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ $logoSrc ?? public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">VOUCHER PEMBAYARAN</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Nama Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->name ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($voucher->voucher_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">No. Voucher</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $voucher->voucher_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">RAPP</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->name ?? ('RAPP #'.$rab->id) }}</td>
            <td class="meta-label">Sumber</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $sourceLabel }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $voucher->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($voucher->created_at ?? null) }} / {{ $fmtDateTime($voucher->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="mt-6">
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:12%;">Kode Item</th>
                <th>Uraian</th>
                <th style="width:8%;">Qty</th>
                <th style="width:8%;">Sat</th>
                <th style="width:15%;">Harga Satuan</th>
                <th style="width:15%;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voucher->items ?? [] as $item)
                @php
                    $rabItem = $item->rabItem;
                    $data = $rabItem?->data;
                    $kode = $data->kode ?? $data->item_code ?? $item->item_code_snapshot ?? '';
                    $uraian = $data->uraian ?? $data->item_name ?? $item->item_name_snapshot ?? '';
                    $qty = (float) ($item->qty ?? 0);
                    $price = (float) ($item->price ?? 0);
                    $amount = (float) ($item->amount ?? ($qty * $price));
                    $unit = $data->satuan ?? $rabItem?->satuan ?? $item->unit ?? '';
                @endphp
                <tr>
                    <td class="text-center">{{ $kode ?: '-' }}</td>
                    <td>{{ $uraian ?: '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $unit ?: '-' }}</td>
                    <td class="text-right">{{ $fmtRp($price) }}</td>
                    <td class="text-right">{{ $fmtRp($amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"></td>
                <td class="text-right"><strong>PPN 11%</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($tax) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="text-right"><strong>ONGKIR</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($voucher->shipping_cost ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="text-right"><strong>TOTAL TAGIHAN</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($total) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="mt-6">
    <table class="doc-meta">
        <tr>
            <td><span class="label">Ket</span> : {{ $voucher->notes ?? '-' }}</td>
        </tr>
    </table>
</div>

<div class="mt-6">
    <table class="doc-table">
        <tr>
            <th colspan="2">Tujuan Transfer</th>
            <th>Diajukan Oleh</th>
            <th>Disetujui Oleh</th>
        </tr>
        <tr>
            <td><strong>Vendor</strong></td>
            <td>{{ $voucher->vendor_name ?? optional($voucher->vendor)->nama ?? '-' }}</td>
            <td rowspan="4" class="text-center">{{ $voucher->requested_by ?? '-' }}</td>
            <td rowspan="4" class="text-center">{{ $voucher->approved_by ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Bank</strong></td>
            <td>{{ $voucher->bank ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Nama</strong></td>
            <td>{{ $voucher->account_name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>No. Rek</strong></td>
            <td>{{ $voucher->account_no ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Pembayaran</strong></td>
            <td>{{ $voucher->payment_method ?? '-' }}</td>
            <td><strong>Jatuh Tempo</strong></td>
            <td>{{ $fmtDate($voucher->due_date ?? null) }}</td>
        </tr>
    </table>
</div>

@if(($voucher->status ?? '') === 'rejected' && ($voucher->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $voucher->rejected_reason }}
    </div>
@endif
@endsection


