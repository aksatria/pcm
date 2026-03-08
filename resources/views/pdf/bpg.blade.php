@extends('pdf.layout')

@section('title', 'BPG')
@section('header_title', 'BON PERMINTAAN GUDANG (BPG)')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
@endphp

<style>
    .bpg-box { border: 1px solid #111; padding: 6px 8px; }
    .bpg-title { font-size: 14px; font-weight: 700; text-align: center; text-transform: uppercase; }
    .bpg-meta td { padding: 3px 4px; vertical-align: top; font-size: 11px; }
    .bpg-note { border: 1px solid #111; padding: 6px 8px; font-size: 10px; line-height: 1.35; }
    .bpg-table th,
    .bpg-table td { border: 1px solid #111; padding: 4px; font-size: 10px; }
    .bpg-table th { background: #e9eff7; text-transform: uppercase; font-size: 9px; font-weight: 700; }
    .bpg-table tbody tr:nth-child(even) td { background: #f6f8fb; }
    .bpg-sign td { border: 1px solid #111; height: 70px; vertical-align: top; font-size: 10px; }
    .bpg-sign-label { font-size: 10px; font-weight: 700; }
    .bpg-sign-role { font-size: 9px; color: #333; }
</style>

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ $logoSrc ?? public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">BON PERMINTAAN GUDANG (BPG)</td>
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
            <td class="meta-value">{{ $bpg->bpg_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($bpg->bpg_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $bpg->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($bpg->created_at ?? null) }} / {{ $fmtDateTime($bpg->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="bpg-note mt-6">
    <strong>CATATAN:</strong>
    <div>1. Sebelum mengajukan, pastikan barang yang akan diminta telah tersedia (di Gudang).</div>
    <div>2. Barang dinyatakan telah disampaikan dan diterima oleh yang meminta, jika Bon ini telah ditanda-tangani oleh Kepala Gudang.</div>
</div>

<div class="mt-6">
    <table class="bpg-table">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:30%;">Uraian Item</th>
                <th style="width:12%;">Kode Item</th>
                <th style="width:8%;">Qty</th>
                <th style="width:8%;">Sat</th>
                <th style="width:14%;">Tanggal Keluar</th>
                <th>Keterangan (Pekerjaan)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bpg->items ?? [] as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row->item_name_snapshot ?? $row->rabItem?->data?->uraian ?? '-' }}</td>
                    <td class="text-center">{{ $row->item_code_snapshot ?? $row->rabItem?->data?->kode ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->unit ?? $row->unit_snapshot ?? '-' }}</td>
                    <td class="text-center">{{ $fmtDate($row->issue_date ?? null) }}</td>
                    <td>{{ $row->work_notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-10">
    <table class="bpg-sign">
        <tr>
            <td>
                <div class="bpg-sign-label">1. DIAJUKAN OLEH :</div>
                <div class="bpg-sign-role">STAFF/PELAKSANA</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $bpg->requested_by ?? '-' }}</div>
                <div>TANGGAL : {{ $fmtDate($bpg->bpg_date ?? null) }}</div>
            </td>
            <td>
                <div class="bpg-sign-label">2. DISETUJUI OLEH :</div>
                <div class="bpg-sign-role">PROJECT MANAGER</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $bpg->approved_by ?? '-' }}</div>
                <div>TANGGAL : -</div>
            </td>
            <td>
                <div class="bpg-sign-label">3. DIKETAHUI OLEH :</div>
                <div class="bpg-sign-role">KA. DEP. PROCUREMENT</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $bpg->known_by ?? '-' }}</div>
                <div>TANGGAL : -</div>
            </td>
        </tr>
    </table>
</div>
@if(($bpg->status ?? '') === 'rejected' && ($bpg->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $bpg->rejected_reason }}
    </div>
@endif
@endsection
