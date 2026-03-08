@extends('layouts.dev')

@section('title', 'Dokumentasi Role & Permission')

@section('content')
<div class="p-6 space-y-8 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Dokumentasi Role & Permission</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Ringkasan hak akses per modul untuk Staff dan HO (Head Office).</p>
    </div>
    <a href="{{ route('dev.dashboard') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
      Kembali ke Dashboard
    </a>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Definisi Role</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="rounded-xl border border-sky-200 dark:border-sky-900/60 bg-sky-50/60 dark:bg-sky-900/10 p-4">
        <div class="text-[11px] font-semibold text-sky-700 dark:text-sky-300 uppercase tracking-wide">Staff</div>
        <div class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200">
          Membuat dan mengelola dokumen operasional saat status masih <strong>draft</strong> atau <strong>rejected</strong>,
          lalu submit untuk approval.
        </div>
      </div>
      <div class="rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-900/10 p-4">
        <div class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">HO (Head Office)</div>
        <div class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200">
          Memiliki akses penuh termasuk approval, pembatalan, dan master data.
          HO dapat mengedit dokumen yang sudah approved bila diperlukan.
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Aturan Umum Workflow</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/20 p-4">
        <div class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Draft / Rejected</div>
        <div class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200">Bisa edit oleh Staff dan HO.</div>
      </div>
      <div class="rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/60 dark:bg-indigo-900/10 p-4">
        <div class="text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">Submitted</div>
        <div class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200">Terkunci untuk Staff. HO dapat approve/reject.</div>
      </div>
      <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/60 dark:bg-emerald-900/10 p-4">
        <div class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">Approved</div>
        <div class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200">Terkunci untuk Staff. HO bisa edit jika diperlukan.</div>
      </div>
    </div>
    <div class="mt-4 text-[13px] text-gray-600 dark:text-gray-300">
      Khusus Master Data: penghapusan oleh Staff akan berstatus <strong>pending delete</strong> dan menunggu approval HO.
      Item dengan status <strong>nonaktif</strong> dapat dihapus langsung.
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Cara Pemakaian (Ringkas)</h2>
    </div>
    <ol class="text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
      <li>Buat proyek dan pastikan RAPP aktif tersedia.</li>
      <li>Staff/HO membuat dokumen (SPP/PO/SPK/Komparasi/LPB/BPG/Voucher) dalam status draft.</li>
      <li>Pastikan item diambil dari RAPP proyek aktif dan qty > 0.</li>
      <li>Submit dokumen untuk approval HO.</li>
      <li>HO approve/reject melalui Approval Center atau tombol Approve/Reject.</li>
      <li>Dokumen approved menjadi referensi untuk dokumen turunan berikutnya.</li>
    </ol>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-fuchsia-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Urutan Pengisian Dokumen (Sesuai Sistem)</h2>
    </div>
    <ol class="text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-3 list-decimal list-inside">
      <li><strong>RAPP</strong>: rencana anggaran proyek. Isi item & harga satuan, lalu submit untuk approval HO.</li>
      <li><strong>SPP</strong>: permintaan kebutuhan dari RAPP. Pilih item RAPP (qty <= sisa RAPP), isi schedule & keterangan.</li>
      <li><strong>Komparasi Vendor</strong> (opsional): bandingkan harga vendor per item sebelum memilih vendor.</li>
      <li><strong>SPK</strong> (opsional): surat perintah kerja ke vendor terpilih (bisa mengikuti Komparasi).</li>
      <li><strong>PO</strong>: pesanan pembelian ke vendor. Pilih item RAPP (qty <= sisa RAPP), lalu submit & approve.</li>
      <li><strong>LPB</strong>: bukti penerimaan barang. Wajib pilih PO approved, qty <= sisa PO.</li>
      <li><strong>BPG</strong>: permintaan barang dari gudang. Wajib pilih LPB approved, qty <= sisa LPB.</li>
      <li><strong>Voucher Pembelian</strong>: pembayaran ke vendor. Wajib referensi PO/LPB approved, vendor & item harus match.</li>
    </ol>
    <div class="mt-3 text-[13px] text-gray-600 dark:text-gray-300">
      Catatan: Komparasi & SPK bersifat opsional. Jika tidak diperlukan, langsung lanjut PO.
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Aturan Validasi & Keterkaitan</h2>
    </div>
    <ul class="text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-disc list-inside">
      <li>Item yang dipilih wajib berasal dari RAPP proyek aktif.</li>
      <li>Qty harus numeric dan > 0.</li>
      <li>Qty tidak boleh melebihi sisa RAPP.</li>
      <li>LPB wajib memilih PO yang approved. Qty LPB <= sisa PO per item.</li>
      <li>BPG wajib memilih LPB yang approved. Qty BPG <= sisa LPB per item.</li>
      <li>Voucher Pembelian wajib referensi PO/LPB yang approved dan vendor/item harus match.</li>
      <li>Qty voucher <= sisa PO/LPB yang belum terbayar.</li>
      <li>Status submitted/approved terkunci untuk Staff (HO dapat koreksi khusus).</li>
      <li>PO dibatalkan -> LPB/Voucher terkait otomatis ditolak/dikunci.</li>
    </ul>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Flow Kerja (End-to-End)</h2>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
      <div class="rounded-xl border border-cyan-200 dark:border-cyan-900/60 bg-cyan-50/60 dark:bg-cyan-900/10 p-4">
        <div class="text-[11px] font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide">RAPP & Perencanaan</div>
        <ol class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
          <li>HO dan Staff membuat RAPP proyek (termasuk item & harga satuan).</li>
          <li>HO dan Staff dapat submit RAPP. Approval tetap hanya oleh HO.</li>
          <li>Setelah approved, dokumen turunan menggunakan RAPP ini sebagai referensi.</li>
        </ol>
      </div>
      <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/60 dark:bg-emerald-900/10 p-4">
        <div class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">Permintaan & Pengadaan</div>
        <ol class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
          <li>Staff membuat SPP dengan item dari RAPP (qty <= sisa RAPP).</li>
          <li>Komparasi vendor dibuat untuk membandingkan harga (opsional untuk SPK).</li>
          <li>PO dibuat dari item RAPP dan disubmit.</li>
          <li>HO approve PO.</li>
        </ol>
      </div>
      <div class="rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-900/10 p-4">
        <div class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">Penerimaan & Pengeluaran</div>
        <ol class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
          <li>LPB wajib memilih PO yang approved. Qty LPB tidak boleh melebihi sisa PO.</li>
          <li>HO approve LPB -> stok masuk tercatat otomatis.</li>
          <li>BPG wajib memilih LPB yang approved. Qty BPG tidak boleh melebihi sisa LPB.</li>
          <li>HO approve BPG -> stok keluar tercatat otomatis.</li>
        </ol>
      </div>
      <div class="rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/60 dark:bg-rose-900/10 p-4">
        <div class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 uppercase tracking-wide">Pembayaran & Voucher</div>
        <ol class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
          <li>Voucher Pembelian wajib referensi PO/LPB yang approved.</li>
          <li>Vendor & item harus match, qty voucher <= sisa PO/LPB yang belum terbayar.</li>
          <li>Staff submit voucher, HO approve/reject. Status paid/completed hanya HO.</li>
        </ol>
      </div>
      <div class="rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-900/10 p-4">
        <div class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">Master Data Delete Approval</div>
        <ol class="mt-2 text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
          <li>Staff melakukan delete pada Data/Client/Vendor.</li>
          <li>Sistem mengubah status menjadi <strong>pending delete</strong>.</li>
          <li>HO melakukan approve/reject. Jika approve, data dihapus permanen.</li>
        </ol>
      </div>
    </div>
    <div class="mt-4 text-[13px] text-gray-600 dark:text-gray-300">
      Catatan: Dokumen status <strong>submitted</strong> atau <strong>approved</strong> terkunci untuk Staff. HO dapat melakukan koreksi khusus bila diperlukan.
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="flex items-center gap-2 mb-4">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-violet-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Hak Akses Per Modul</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-violet-50/70 dark:bg-violet-900/20">
          <tr>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Modul</th>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Staff</th>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">HO</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-[13px] leading-relaxed">
          <tr>
            <td class="px-3 py-2 font-medium">SPP / BPG / LPB / PO / SPK / Komparasi / Voucher Pembelian</td>
            <td class="px-3 py-2">Create, Edit (draft/rejected), Submit</td>
            <td class="px-3 py-2">Full access + Approve/Reject</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">RAPP (Per Proyek)</td>
            <td class="px-3 py-2">Create/Edit (draft/rejected), Submit</td>
            <td class="px-3 py-2">Create/Edit/Delete, Import, Duplicate, Approve/Reject</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">RAPP Global</td>
            <td class="px-3 py-2">Tidak ada akses</td>
            <td class="px-3 py-2">View (index/summary)</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Voucher (Non-Purchase)</td>
            <td class="px-3 py-2">Create/Edit (draft), Submit</td>
            <td class="px-3 py-2">Approve/Reject, Mark Paid/Completed</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Laporan Stok (Rekap/Kartu)</td>
            <td class="px-3 py-2">View, Print, PDF</td>
            <td class="px-3 py-2">View, Print, PDF</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Report Export (PDF/Excel)</td>
            <td class="px-3 py-2">Akses export yang tersedia di modul terkait</td>
            <td class="px-3 py-2">Akses export yang tersedia di modul terkait</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Master Data (Data, Clients, Vendors)</td>
            <td class="px-3 py-2">Create/Edit/Import. Delete = request (pending HO).</td>
            <td class="px-3 py-2">Full CRUD + Approve/Reject delete</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">RAB Breakdown</td>
            <td class="px-3 py-2">Create/Edit/Delete (non-submitted), Submit</td>
            <td class="px-3 py-2">Full CRUD + Approve/Reject</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Approval Center</td>
            <td class="px-3 py-2">Tidak ada akses</td>
            <td class="px-3 py-2">Full access</td>
          </tr>
          <tr>
            <td class="px-3 py-2 font-medium">Audit Logs</td>
            <td class="px-3 py-2">Tidak ada akses</td>
            <td class="px-3 py-2">View only</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
    <div class="flex items-center gap-2 mb-3">
      <span class="inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Catatan Penting</h2>
    </div>
    <ol class="text-[13px] leading-relaxed text-gray-700 dark:text-gray-200 space-y-2 list-decimal list-inside">
      <li>Jika dokumen sudah <strong>submitted</strong> atau <strong>approved</strong>, Staff tidak dapat mengubah data.</li>
      <li>HO dapat melakukan perubahan khusus pada dokumen approved jika diperlukan untuk koreksi.</li>
      <li>Approval hanya bisa dilakukan oleh HO melalui Approval Center atau tombol Approve/Reject.</li>
      <li>Penghapusan Master Data oleh Staff akan masuk antrian approval HO (pending delete).</li>
    </ol>
  </div>
</div>
@endsection


