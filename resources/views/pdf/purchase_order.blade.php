@extends('pdf.layout')

@section('title', 'Purchase Order')
@section('header_title', 'PURCHASE ORDER')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $subtotal = (float) ($purchaseOrder->subtotal_amount ?? 0);
    $taxPercent = (float) ($purchaseOrder->tax_percent ?? 0);
    $taxAmount = (float) ($purchaseOrder->tax_amount ?? 0);
    $shipping = (float) ($purchaseOrder->shipping_cost ?? 0);
    $total = (float) ($purchaseOrder->total_amount ?? 0);
    $vendor = $purchaseOrder->vendor;
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ $logoSrc ?? public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">PURCHASE ORDER</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Nomor</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->po_no ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($purchaseOrder->po_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($purchaseOrder->created_at ?? null) }} / {{ $fmtDateTime($purchaseOrder->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="mt-6 meta-box">
    <table class="meta-grid">
        <tr>
            <td class="meta-label">Dibuat Oleh</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ config('app.name') }}</td>
            <td class="meta-label">Order To</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $vendor->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Alamat</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->address ?? '-' }}</td>
            <td class="meta-label">Alamat</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $vendor->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">No. Telepon</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->phone ?? '-' }}</td>
            <td class="meta-label">NPWP</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $vendor->npwp ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Contact Person</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->contact_person ?? '-' }}</td>
            <td class="meta-label">Phone</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseOrder->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->name ?? '-' }}</td>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">RAPP</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->name ?? ('RAPP #'.$rab->id) }}</td>
            <td class="meta-label">PO Date</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($purchaseOrder->po_date ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="mt-6 doc-title-center" style="font-size:11px;">DAFTAR URAIAN PEMESANAN</div>
<div class="mt-6">
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th>Uraian</th>
                <th style="width:16%;">Spesifikasi</th>
                <th style="width:8%;">Qty</th>
                <th style="width:8%;">Sat</th>
                <th style="width:12%;">Harga Satuan</th>
                <th style="width:12%;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items ?? [] as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row->item_name_snapshot ?? $row->rabItem?->data?->uraian ?? '-' }}</td>
                    <td>{{ $row->specification ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->unit ?? $row->unit_snapshot ?? '-' }}</td>
                    <td class="text-right">{{ $fmtRp($row->unit_price ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->total_price ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada item.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($subtotal) }}</strong></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><strong>PPN ({{ number_format($taxPercent, 2, ',', '.') }}%)</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($taxAmount) }}</strong></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><strong>ONGKIR</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($shipping) }}</strong></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($total) }}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mt-6">
    <table class="doc-meta">
        <tr>
            <td><span class="label">Term of Payment</span> : Tagihan lengkap diterima</td>
        </tr>
        <tr>
            <td><span class="label">Kelengkapan Dokumen Tagihan</span> : Invoice, Kwitansi Asli, Faktur Pajak (jika ada), LPB, Copy PO</td>
        </tr>
        <tr>
            <td><span class="label">MOS</span> : Mengikuti schedule pengiriman material proyek</td>
        </tr>
        <tr>
            <td><span class="label">Alamat Kirim</span> : {{ $project->name ?? '-' }}</td>
        </tr>
    </table>
</div>

<div class="mt-10">
    <table class="doc-sign">
        <tr>
            <td>
                <div class="doc-sign-label">Dibuat Oleh:</div>
                <div class="sign-role">Procurement</div>
                <div style="height:42px;"></div>
                <div>Nama: -</div>
            </td>
            <td>
                <div class="doc-sign-label">Disetujui Oleh:</div>
                <div class="sign-role">Direktur Keuangan</div>
                <div style="height:42px;"></div>
                <div>Nama: -</div>
            </td>
            <td>
                <div class="doc-sign-label">Disetujui Oleh:</div>
                <div class="sign-role">Vendor</div>
                <div style="height:42px;"></div>
                <div>Nama: {{ $vendor->nama ?? '-' }}</div>
            </td>
        </tr>
    </table>
</div>
@if(($purchaseOrder->status ?? '') === 'rejected' && ($purchaseOrder->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $purchaseOrder->rejected_reason }}
    </div>
@endif
@endsection


