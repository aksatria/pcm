@extends('pdf.layout')

@section('title', 'SPP')
@section('header_title', 'SURAT PERMINTAAN PENGADAAN/PENYERAHAN (SPP)')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtDateTime = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y H:i') : '-';
@endphp

<div class="doc-header-box">
    <table class="doc-header-line">
        <tr>
            <td class="doc-header-logo">
                <img class="doc-logo" src="{{ public_path('images/logo-dipo.png') }}" alt="Logo">
            </td>
            <td class="doc-header-title">SURAT PERMINTAAN PENGADAAN/PENYERAHAN (SPP)</td>
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
            <td class="meta-value">{{ $spp->spp_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Proyek</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project->code ?? '-' }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDate($spp->spp_date ?? null) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $spp->status ?? '-' }}</td>
            <td class="meta-label">Dibuat / Diubah</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $fmtDateTime($spp->created_at ?? null) }} / {{ $fmtDateTime($spp->updated_at ?? null) }}</td>
        </tr>
    </table>
</div>

<div class="note-box mt-6">
    <strong>CATATAN:</strong>
    <div>1. SATU LEMBAR SPP/P HANYA DIGUNAKAN UNTUK SATU ITEM BUDGET.</div>
    <div>2. SPP/P DISAMPAIKAN KE COMPTROLLER PUSAT PALING LAMBAT 10 HARI SEBELUM TANGGAL SCHEDULE BARANG TIBA DAN TERLAMPIR CSPB (CONTROL STATUS PENGADAAN BARANG).</div>
    <div>3. SPP/P DIINPUT KE DALAM SdBP+ SETELAH LENGKAP KOLOM PERSETUJUAN HINGGA PM.</div>
    <div>4. KHUSUS UNTUK PENGADAAN ALAT, RENCANA PERIODE WAKTU PEMAKAIAN AGAR DITULISKAN DALAM KOLOM KETERANGAN.</div>
    <div>5. SESUAI PERSYARATAN SMTP.</div>
    <div>6. UNTUK KANTOR PUSAT SPP CUKUP S/D KOLOM 3 (COMPTROLLER).</div>
    <div>7. *): CORET YANG TIDAK PERLU.</div>
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
                <th style="width:14%;">Schedule Barang Tiba (Tanggal)</th>
                <th style="width:16%;">Keterangan (Pekerjaan)</th>
                <th colspan="2">Rekomendasi</th>
            </tr>
            <tr>
                <th colspan="7"></th>
                <th style="width:10%;">Pemasok/Supplier</th>
                <th style="width:10%;">Kontak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($spp->items ?? [] as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row->item_name_snapshot ?? $row->rabItem?->data?->uraian ?? '-' }}</td>
                    <td class="text-center">{{ $row->item_code_snapshot ?? $row->rabItem?->data?->kode ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $row->unit ?? $row->unit_snapshot ?? '-' }}</td>
                    <td class="text-center">{{ $fmtDate($row->schedule_date ?? null) }}</td>
                    <td>{{ $row->work_notes ?? '-' }}</td>
                    <td>{{ optional($row->vendor)->nama ?? '-' }}</td>
                    <td>{{ optional($row->vendor)->telepon ?? '-' }}</td>
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
                <div class="doc-sign-label">1. Diajukan Oleh :</div>
                <div class="sign-role">PELAKSANA/STAFF</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $spp->requested_by ?? '-' }}</div>
                <div>TANGGAL : {{ $fmtDate($spp->spp_date ?? null) }}</div>
            </td>
            <td>
                <div class="doc-sign-label">2. Diperiksa Oleh :</div>
                <div class="sign-role">PROJECT MANAGER</div>
                <div style="height:42px;"></div>
                <div>NAMA : -</div>
                <div>TANGGAL : -</div>
            </td>
            <td>
                <div class="doc-sign-label">3. Disetujui Oleh :</div>
                <div class="sign-role">KA. DEP. PROCUREMENT</div>
                <div style="height:42px;"></div>
                <div>NAMA : {{ $spp->approved_by ?? '-' }}</div>
                <div>TANGGAL : -</div>
            </td>
        </tr>
    </table>
</div>
@if(($spp->status ?? '') === 'rejected' && ($spp->rejected_reason ?? null))
    <div class="note-box mt-6">
        <strong>Alasan Reject:</strong> {{ $spp->rejected_reason }}
    </div>
@endif
@endsection
