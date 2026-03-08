@extends('pdf.layout')

@section('title', 'Rekap Voucher Pembelian')
@section('header_title', 'REKAP VOUCHER PEMBELIAN')

@section('content')
@php
    $fmtDate = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $subtotalSum = (float) ($totals->subtotal_sum ?? 0);
    $taxSum = (float) ($totals->tax_sum ?? 0);
    $totalSum = (float) ($totals->total_sum ?? 0);
@endphp

<style>
    .doc-title-center { font-size: 14px; font-weight: 700; text-align: center; text-transform: uppercase; }
    .doc-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .doc-table th,
    .doc-table td { border: 1px solid #111; padding: 4px 5px; font-size: 10px; }
    .doc-table th { text-transform: uppercase; font-size: 9px; font-weight: 700; }
    .doc-sign { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .doc-sign td { border: 1px solid #111; height: 70px; vertical-align: top; font-size: 10px; padding: 6px; }
    .doc-sign-label { font-size: 10px; font-weight: 700; text-transform: uppercase; }
</style>

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">REKAP VOUCHER PEMBELIAN</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Nama Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->name ?? '-' }}</td>
            <td class="meta-label">Periode</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($filters['date_from'] ?? null) }} s/d {{ $fmtDate($filters['date_to'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">RAPP</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->name ?? ('RAPP #'.$rab->id) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Filter Vendor</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $filters['vendor'] ?? '-' }}</td>
            <td class="meta-label">Kata Kunci</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $filters['q'] ?? '-' }}</td>
        </tr>
    </table>
</div>

<div class="mt-6">
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:10%;">Tanggal</th>
                <th style="width:14%;">No. Voucher</th>
                <th style="width:12%;">Sumber</th>
                <th>Vendor</th>
                <th style="width:12%;">Invoice</th>
                <th style="width:10%;">Subtotal</th>
                <th style="width:10%;">PPN</th>
                <th style="width:12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vouchers as $i => $voucher)
                @php
                    $sourceLabel = $voucher->lpb_id
                        ? 'LPB ' . ($voucher->lpb?->lpb_no ?? ('#'.$voucher->lpb_id))
                        : 'PO ' . ($voucher->purchaseOrder?->po_no ?? ($voucher->purchase_order_id ? ('#'.$voucher->purchase_order_id) : '-'));
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-center">{{ $voucher->voucher_date ? $voucher->voucher_date->format('d M Y') : '-' }}</td>
                    <td>{{ $voucher->voucher_no ?? '-' }}</td>
                    <td>{{ $sourceLabel }}</td>
                    <td>{{ $voucher->vendor_name ?? '-' }}</td>
                    <td>{{ $voucher->invoice_no ?? '-' }}</td>
                    <td class="text-right">{{ $fmtRp($voucher->subtotal_amount ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($voucher->tax_amount ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($voucher->total_amount ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada voucher.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($subtotalSum) }}</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($taxSum) }}</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($totalSum) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="mt-10">
    <table class="doc-sign">
        <tr>
            <td>
                <div class="doc-sign-label">Dibuat</div>
                <div style="height:42px;"></div>
                <div>NAMA :</div>
                <div>TANGGAL :</div>
            </td>
            <td>
                <div class="doc-sign-label">Diperiksa</div>
                <div style="height:42px;"></div>
                <div>NAMA :</div>
                <div>TANGGAL :</div>
            </td>
            <td>
                <div class="doc-sign-label">Disetujui</div>
                <div style="height:42px;"></div>
                <div>NAMA :</div>
                <div>TANGGAL :</div>
            </td>
        </tr>
    </table>
</div>
@endsection


