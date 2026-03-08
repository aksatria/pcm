@extends('pdf.layout')

@section('title', 'LPB')
@section('header_title', 'LAPORAN PENERIMAAN BARANG (LPB)')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ $logoSrc ?? public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">LAPORAN PENERIMAAN BARANG (LPB)</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Proyek/Bagian</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->name ?? '-' }}</td>
            <td class="meta-label">Nomor</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $lpb->lpb_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($lpb->lpb_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $lpb->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($lpb->created_at ?? null) }} / {{ $fmtDateTime($lpb->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="note-box mt-6">
    <strong>CATATAN:</strong>
    <div>Laporan Penerimaan Barang (LPB) digunakan sebagai bukti bahwa material yang diorder telah sampai dan diterima oleh tim proyek untuk di rekap pada Kartu Stock dan Laporan Sediaan Barang (LSB).</div>
</div>

<div class="mt-6">
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:26%;">Uraian Item</th>
                <th style="width:10%;">Kode Item</th>
                <th style="width:8%;">Qty</th>
                <th style="width:8%;">Sat</th>
                <th style="width:12%;">Tanggal Tiba</th>
                <th style="width:16%;">Keterangan (Pekerjaan)</th>
                <th colspan="2">Dok. Referensi</th>
            </tr>
            <tr>
                <th colspan="7"></th>
                <th style="width:10%;">Pemasok/Supplier</th>
                <th style="width:10%;">No. Voucher</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lpb->items ?? [] as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row->item_name_snapshot ?? $row->rabItem?->data?->uraian ?? '-' }}</td>
                    <td class="text-center">{{ $row->item_code_snapshot ?? $row->rabItem?->data?->kode ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->unit ?? $row->unit_snapshot ?? '-' }}</td>
                    <td class="text-center">{{ $fmtDate($row->arrival_date ?? null) }}</td>
                    <td>{{ $row->work_notes ?? '-' }}</td>
                    <td>{{ optional($lpb->vendor)->nama ?? '-' }}</td>
                    <td>{{ $lpb->purchaseOrder?->po_no ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-10">
    <table class="doc-sign">
        <tr>
            <td>
                <div class="doc-sign-label">1. Dikirim Oleh :</div>
                <div class="sign-role">VENDOR</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ optional($lpb->vendor)->nama ?? '-' }}</div>
                <div>TANGGAL : {{ $fmtDate($lpb->lpb_date ?? null) }}</div>
            </td>
            <td>
                <div class="doc-sign-label">2. Diterima Oleh :</div>
                <div class="sign-role">PROJECT MANAGER</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $lpb->received_by ?? '-' }}</div>
                <div>TANGGAL : -</div>
            </td>
            <td>
                <div class="doc-sign-label">3. Diketahui Oleh :</div>
                <div class="sign-role">KA. DEP. PROCUREMENT</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $lpb->known_by ?? '-' }}</div>
                <div>TANGGAL : -</div>
            </td>
        </tr>
    </table>
</div>
@if(($lpb->status ?? '') === 'rejected' && ($lpb->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $lpb->rejected_reason }}
    </div>
@endif
@endsection
