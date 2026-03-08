@extends('pdf.layout')

@section('title', 'Voucher Pembayaran')
@section('header_title', 'VOUCHER PEMBAYARAN')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
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
            <td class="meta-value">{{ $fmtDate($voucher->tanggal ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Project</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">No. Voucher</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $voucher->voucher_number ?? '-' }}</td>
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
                <th style="width:14%;">Kode Item</th>
                <th>Uraian</th>
                <th style="width:8%;">Qty</th>
                <th style="width:8%;">Sat</th>
                <th style="width:14%;">Harga Satuan</th>
                <th style="width:14%;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voucher->items ?? [] as $row)
                <tr>
                    <td class="text-center">{{ $row->kode ?? '-' }}</td>
                    <td>{{ $row->uraian ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->satuan ?? '-' }}</td>
                    <td class="text-right">{{ $fmtRp($row->harga_satuan ?? 0) }}</td>
                    <td class="text-right">{{ $fmtRp($row->total_harga ?? 0) }}</td>
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
                <td class="text-right"><strong>{{ $fmtRp($voucher->ppn ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="text-right"><strong>ONGKIR</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($voucher->ongkir ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="text-right"><strong>TOTAL TAGIHAN</strong></td>
                <td class="text-right"><strong>{{ $fmtRp($voucher->total_tagihan ?? 0) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="mt-6">
    <table class="doc-meta">
        <tr>
            <td><span class="label">Ket</span> : {{ $voucher->keterangan ?? '-' }}</td>
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
            <td>{{ optional($voucher->vendor)->nama ?? '-' }}</td>
            <td rowspan="4" class="text-center">{{ $voucher->diajukan_oleh ?? '-' }}</td>
            <td rowspan="4" class="text-center">{{ $voucher->disetujui_oleh ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Bank</strong></td>
            <td>{{ $voucher->bank ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Nama</strong></td>
            <td>{{ $voucher->nama_rekening ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>No. Rek</strong></td>
            <td>{{ $voucher->no_rekening ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Pembayaran</strong></td>
            <td>{{ $voucher->pembayaran ?? '-' }}</td>
            <td><strong>Jatuh Tempo</strong></td>
            <td>{{ $fmtDate($voucher->jatuh_tempo ?? null) }}</td>
        </tr>
    </table>
</div>
@if(($voucher->status ?? '') === 'rejected' && ($voucher->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $voucher->rejected_reason }}
    </div>
@endif
@endsection
