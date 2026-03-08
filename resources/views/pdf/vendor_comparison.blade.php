@extends('pdf.layout')

@section('title', 'Komparasi Vendor')
@section('header_title', 'EVALUASI PENGADAAN VENDOR')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $first = $comparison->items->first();
    $vendor1 = $first?->vendor1;
    $vendor2 = $first?->vendor2;
    $vendor3 = $first?->vendor3;
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">EVALUASI PENGADAAN VENDOR</td>
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
            <td class="meta-value">{{ $comparison->comparison_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($comparison->comparison_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $comparison->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($comparison->created_at ?? null) }} / {{ $fmtDateTime($comparison->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="mt-6 doc-title-center" style="font-size:11px;">TABEL KOMPARASI &amp; PENAWARAN</div>
<div class="mt-6">
    <table class="doc-table">
        <thead>
            <tr>
                <th colspan="6">RAPP</th>
                <th colspan="2">SUPLYER 1 : {{ $vendor1->nama ?? '-' }}</th>
                <th colspan="2">SUPLYER 2 : {{ $vendor2->nama ?? '-' }}</th>
                <th colspan="2">SUPLYER 3 : {{ $vendor3->nama ?? '-' }}</th>
            </tr>
            <tr>
                <th style="width:4%;">No</th>
                <th>Uraian Item</th>
                <th style="width:6%;">Qty</th>
                <th style="width:6%;">Satuan</th>
                <th style="width:10%;">Harga Satuan</th>
                <th style="width:10%;">Total</th>
                <th style="width:10%;">Harga Satuan</th>
                <th style="width:10%;">Total</th>
                <th style="width:10%;">Harga Satuan</th>
                <th style="width:10%;">Total</th>
                <th style="width:10%;">Harga Satuan</th>
                <th style="width:10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comparison->items ?? [] as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row->item_name_snapshot ?? $row->rabItem?->data?->uraian ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->unit ?? $row->unit_snapshot ?? '-' }}</td>
                    <td class="text-right">{{ $fmtRp($row->rapp_unit_price ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->rapp_total ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor1_unit_price ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor1_total ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor2_unit_price ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor2_total ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor3_unit_price ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->vendor3_total ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>PPN 11%</strong></td>
                <td class="text-center">11%</td>
                <td class="text-right"><strong>{{ $fmtRp($comparison->tax_amount ?? 0) }}</strong></td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><strong>ONGKIR</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($comparison->shipping_cost ?? 0) }}</strong></td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><strong>BUNGA TEMPO 2,5% / BULAN</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($comparison->interest_amount ?? 0) }}</strong></td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><strong>JUMLAH</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($comparison->final_amount ?? 0) }}</strong></td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><strong>SELISIH TERHADAP RAPP</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($comparison->difference_amount ?? 0) }}</strong></td>
                <td colspan="6"></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="mt-6">
    <div class="note-box">
        <div><strong>B. SPESIFIKASI DAN LINGKUP PEKERJAAN</strong></div>
        <div>{{ $comparison->notes ?? '-' }}</div>
    </div>
</div>

<div class="mt-10">
    <table class="doc-sign">
        <tr>
            <td>
                <div class="doc-sign-label">Diajukan Oleh:</div>
                <div class="sign-role">Kepala Departemen Procurement</div>
                <div style="height:42px;"></div>
                <div>Nama: -</div>
            </td>
            <td>
                <div class="doc-sign-label">Disetujui Oleh:</div>
                <div class="sign-role">Direktur Keuangan</div>
                <div style="height:42px;"></div>
                <div>Nama: -</div>
            </td>
        </tr>
    </table>
</div>
@if(($comparison->status ?? '') === 'rejected' && ($comparison->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $comparison->rejected_reason }}
    </div>
@endif
@endsection


