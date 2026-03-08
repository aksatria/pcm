@extends('pdf.layout')

@section('title', 'Kartu Stock Gudang')
@section('header_title', 'KARTU STOCK GUDANG')

@section('content')
@php
    $fmtDate = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
    };
    $fmtNumber = function ($n) {
        $n = (float) ($n ?? 0);
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    };
    $inRows = $rows->filter(fn($row) => ($row->movement_type ?? '') === 'in')->values();
    $outRows = $rows->filter(fn($row) => ($row->movement_type ?? '') === 'out')->values();
    $maxRows = max($inRows->count(), $outRows->count());
    $totalIn = $inRows->sum(fn($row) => (float) ($row->qty ?? 0));
    $totalOut = $outRows->sum(fn($row) => (float) ($row->qty ?? 0));
    $updateDate = $dateTo ?? $rows->last()?->movement_date ?? $dateFrom ?? null;
    $cardNo = $rab?->code ?? $rab?->no ?? $rab?->name ?? ('RAPP #' . ($rab?->id ?? '-'));
    $docNumber = function ($row) {
        if (!$row) {
            return '-';
        }
        if (!empty($row->doc_no)) {
            return $row->doc_no;
        }
        if (!empty($row->doc_number)) {
            return $row->doc_number;
        }
        $type = strtoupper((string) ($row->doc_type ?? ''));
        $id = $row->doc_id ?? '';
        return trim(($type ? $type : 'DOC') . ' #' . $id);
    };
    $itemName = function ($row) {
        return $row?->rabItem?->data?->uraian
            ?? $row?->rabItem?->data?->nama
            ?? $row?->rabItem?->uraian_manual
            ?? '-';
    };
    $itemCode = function ($row) {
        return $row?->rabItem?->data?->kode ?? '-';
    };
    $itemUnit = function ($row) {
        return $row?->unit ?? $row?->rabItem?->satuan ?? $row?->rabItem?->data?->satuan ?? '-';
    };
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
            <td class="doc-header-title">KARTU STOCK GUDANG</td>
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
            <td class="meta-label">No. Kartu</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $cardNo }}</td>
            <td class="meta-label">Update per Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($updateDate) }}</td>
        </tr>
    </table>
</div>

<table class="doc-table">
    <thead>
        <tr>
            <th colspan="7">Penerimaan</th>
            <th colspan="7">Pengeluaran</th>
        </tr>
        <tr>
            <th style="width:4%;">No</th>
            <th style="width:9%;">Tanggal</th>
            <th style="width:8%;">Kode</th>
            <th>Material</th>
            <th style="width:9%;">No. LPB</th>
            <th style="width:6%;">Sat</th>
            <th style="width:7%;">Volume</th>
            <th style="width:4%;">No</th>
            <th style="width:9%;">Tanggal</th>
            <th style="width:8%;">Kode</th>
            <th>Material</th>
            <th style="width:9%;">No. BON</th>
            <th style="width:6%;">Sat</th>
            <th style="width:7%;">Volume</th>
        </tr>
    </thead>
    <tbody>
        @if($maxRows === 0)
            <tr>
                <td colspan="14" class="text-center">Tidak ada data.</td>
            </tr>
        @endif
        @for($i = 0; $i < $maxRows; $i++)
            @php
                $in = $inRows[$i] ?? null;
                $out = $outRows[$i] ?? null;
            @endphp
            <tr>
                <td class="text-center">{{ $in ? $i + 1 : '' }}</td>
                <td class="text-center">{{ $in ? $fmtDate($in->movement_date ?? null) : '' }}</td>
                <td>{{ $in ? $itemCode($in) : '' }}</td>
                <td>{{ $in ? $itemName($in) : '' }}</td>
                <td>{{ $in ? $docNumber($in) : '' }}</td>
                <td class="text-center">{{ $in ? $itemUnit($in) : '' }}</td>
                <td class="text-right">{{ $in ? $fmtNumber($in->qty ?? 0) : '' }}</td>
                <td class="text-center">{{ $out ? $i + 1 : '' }}</td>
                <td class="text-center">{{ $out ? $fmtDate($out->movement_date ?? null) : '' }}</td>
                <td>{{ $out ? $itemCode($out) : '' }}</td>
                <td>{{ $out ? $itemName($out) : '' }}</td>
                <td>{{ $out ? $docNumber($out) : '' }}</td>
                <td class="text-center">{{ $out ? $itemUnit($out) : '' }}</td>
                <td class="text-right">{{ $out ? $fmtNumber($out->qty ?? 0) : '' }}</td>
            </tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong>{{ $fmtNumber($totalIn) }}</strong></td>
            <td colspan="6" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong>{{ $fmtNumber($totalOut) }}</strong></td>
        </tr>
    </tfoot>
</table>

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


