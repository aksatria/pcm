@extends('pdf.layout')

@section('title', 'RAPP')
@section('header_title', 'RENCANA ANGGARAN PELAKSANAAN PROYEK (RAPP)')

@section('content')
@php
    $fmtDate = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
    };
    $fmtDateTime = function ($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('d M Y H:i') : '-';
    };
    $fmtRp = function ($n) {
        $n = (float) ($n ?? 0);
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
    $items = $rab->items ?? collect();
    $totalBudget = $items->sum(function ($item) {
        return (float) ($item->volume ?? 0) * (float) ($item->harga_satuan ?? 0);
    });
    $totalRealisasi = $items->sum(function ($item) {
        return (float) ($item->realisasi_volume ?? 0) * (float) ($item->harga_satuan ?? 0);
    });
    $totalSisa = max(0, $totalBudget - $totalRealisasi);
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">RENCANA ANGGARAN PELAKSANAAN PROYEK (RAPP)</td>
            <td class="doc-header-spacer"></td>
        </tr>
    </table>
    <div class="doc-header-rule"></div>
    <table class="meta-grid" style="margin-top:4px;">
        <tr>
            <td class="meta-label">Nama</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->name ?? ('RAPP #'.$rab->id) }}</td>
            <td class="meta-label">Versi</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->version ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $rab->status ?? 'draft' }}</td>
            <td class="meta-label">Tanggal RAPP</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($rab->created_at ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($rab->created_at ?? null) }} / {{ $fmtDateTime($rab->updated_at ?? null) }}</td>
            <td class="meta-label"></td>
            <td class="meta-sep"></td>
            <td class="meta-value"></td>
        </tr>
    </table>
</div>

<div class="mt-6 meta-box">
    <table class="meta-grid">
        <tr>
            <td class="meta-label">Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->name ?? '-' }}</td>
            <td class="meta-label">Client</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ optional($project->client)->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">Jumlah Item</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $items->count() }}</td>
        </tr>
    </table>
</div>

<div class="mt-6 meta-box">
    <table class="meta-grid">
        <tr>
            <td class="meta-label">Total Budget</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtRp($totalBudget) }}</td>
            <td class="meta-label">Realisasi</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtRp($totalRealisasi) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Sisa</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtRp($totalSisa) }}</td>
            <td class="meta-label"></td>
            <td class="meta-sep"></td>
            <td class="meta-value"></td>
        </tr>
    </table>
</div>

<div class="mt-6 doc-title-center" style="font-size:11px;">RINCIAN ITEM</div>
<table class="doc-table">
    <thead>
        <tr>
            <th style="width:4%;">No</th>
            <th style="width:14%;">Kode</th>
            <th>Uraian</th>
            <th style="width:9%;">Volume</th>
            <th style="width:8%;">Satuan</th>
            <th style="width:12%;">Harga</th>
            <th style="width:13%;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $row)
            @php
                $vol = (float) ($row->volume ?? 0);
                $hs = (float) ($row->harga_satuan ?? 0);
                $subtotal = $vol * $hs;
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ optional($row->data)->kode ?? '-' }}</td>
                <td>{{ $row->uraian_manual ?? optional($row->data)->uraian ?? optional($row->data)->nama ?? '-' }}</td>
                <td class="text-right">{{ rtrim(rtrim(number_format($vol, 4, ',', '.'), '0'), ',') }}</td>
                <td class="text-center">{{ $row->satuan ?? optional($row->data)->satuan ?? '-' }}</td>
                <td class="text-right">{{ $fmtRp($hs) }}</td>
                <td class="text-right">{{ $fmtRp($subtotal) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada item.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong>{{ $fmtRp($totalBudget) }}</strong></td>
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
@if(($rab->status ?? '') === 'rejected' && ($rab->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $rab->rejected_reason }}
    </div>
@endif
@endsection


