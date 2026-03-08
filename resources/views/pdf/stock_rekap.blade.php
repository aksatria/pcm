@extends('pdf.layout')

@section('title', 'Laporan Sediaan Barang (LSB)')
@section('header_title', 'LAPORAN SEDIAAN BARANG (LSB)')

@section('content')
@php
    $fmtDate = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
    };
    $fmtNumber = function ($n) {
        $n = (float) ($n ?? 0);
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    };
    $printedAt = now();
    $totalIn = 0;
    $totalOut = 0;
    foreach ($rows as $row) {
        $totalIn += (float) ($row->qty_in ?? 0);
        $totalOut += (float) ($row->qty_out ?? 0);
    }
@endphp

<style>
    .doc-title { font-size: 14px; font-weight: 700; text-align: center; text-transform: uppercase; }
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
                <img class="doc-logo" src="{{ $logoSrc ?? public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">LAPORAN SEDIAAN BARANG (LSB)</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Proyek/Bagian</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project?->name ?? '-' }}</td>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project?->code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Periode</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($dateFrom ?? null) }} s/d {{ $fmtDate($dateTo ?? null) }}</td>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($printedAt) }}</td>
        </tr>
    </table>
</div>

<div class="mt-6">
<table class="doc-table">
    <thead>
        <tr>
            <th style="width:6%;">No</th>
            <th style="width:16%;">Kode Item</th>
            <th>Item</th>
            <th style="width:10%;">Sat</th>
            <th style="width:14%;">Penerimaan</th>
            <th style="width:14%;">Pengeluaran</th>
            <th style="width:12%;">Sisa</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
            @php
                $kode = $row->rabItem?->data?->kode ?? '-';
                $uraian = $row->rabItem?->data?->uraian ?? '-';
                $sat = $row->rabItem?->satuan ?? $row->rabItem?->data?->satuan ?? '-';
                $inQty = (float) ($row->qty_in ?? 0);
                $outQty = (float) ($row->qty_out ?? 0);
                $sisa = $inQty - $outQty;
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $kode }}</td>
                <td>{{ $uraian }}</td>
                <td class="text-center">{{ $sat }}</td>
                <td class="text-right">{{ $fmtNumber($inQty) }}</td>
                <td class="text-right">{{ $fmtNumber($outQty) }}</td>
                <td class="text-right">{{ $fmtNumber($sisa) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada data.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong>{{ $fmtNumber($totalIn) }}</strong></td>
            <td class="text-right"><strong>{{ $fmtNumber($totalOut) }}</strong></td>
            <td class="text-right"><strong>{{ $fmtNumber($totalIn - $totalOut) }}</strong></td>
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
