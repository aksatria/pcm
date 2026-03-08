{{-- resources/views/dev/vendors/edit.blade.php --}}
@extends('layouts.dev')

@section('title', 'Edit Vendor - ' . $vendor->nama)

@section('content')
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-lg font-semibold">Edit Vendor</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Perbarui data vendor berikut.</p>
    </div>
    <a href="{{ route('dev.vendors.index') }}"
       class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
      Kembali
    </a>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      <div class="font-semibold mb-1">Periksa input:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Kode Vendor</p>
        <p class="font-semibold font-mono text-gray-900 dark:text-gray-100">{{ $vendor->kode_vendor }}</p>
      </div>
      <div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Status</p>
        @if($vendor->status == 'active')
          <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">Active</span>
        @else
          <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
        @endif
      </div>
      <div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Total Transaksi</p>
        <p class="font-semibold">{{ $vendor->vouchers_count ?? 0 }} voucher</p>
      </div>
    </div>
  </div>

  <form action="{{ route('dev.vendors.update', $vendor->id) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
      <h2 class="text-sm font-semibold mb-4">Informasi Dasar</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Vendor *</label>
          <input type="text" name="nama" value="{{ old('nama', $vendor->nama) }}" required
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Perusahaan</label>
          <input type="text" name="perusahaan" value="{{ old('perusahaan', $vendor->perusahaan) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Proyek (opsional)</label>
          <select name="project_id"
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
            <option value="">Global (semua proyek)</option>
            @foreach($projects as $project)
              <option value="{{ $project->id }}" {{ (string) old('project_id', $vendor->project_id) === (string) $project->id ? 'selected' : '' }}>
                {{ $project->name }}
              </option>
            @endforeach
          </select>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Jika dipilih, vendor hanya muncul di proyek tersebut.</p>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Jenis Pekerjaan</label>
          <select name="pekerjaan"
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
            <option value="">-- Pilih Jenis Pekerjaan --</option>
            <option value="Toko Bangunan" {{ old('pekerjaan', $vendor->pekerjaan) == 'Toko Bangunan' ? 'selected' : '' }}>Toko Bangunan</option>
            <option value="Supplier Material" {{ old('pekerjaan', $vendor->pekerjaan) == 'Supplier Material' ? 'selected' : '' }}>Supplier Material</option>
            <option value="Jasa Kontraktor" {{ old('pekerjaan', $vendor->pekerjaan) == 'Jasa Kontraktor' ? 'selected' : '' }}>Jasa Kontraktor</option>
            <option value="Jasa Arsitek" {{ old('pekerjaan', $vendor->pekerjaan) == 'Jasa Arsitek' ? 'selected' : '' }}>Jasa Arsitek</option>
            <option value="Jasa Konsultan" {{ old('pekerjaan', $vendor->pekerjaan) == 'Jasa Konsultan' ? 'selected' : '' }}>Jasa Konsultan</option>
            <option value="Logistik" {{ old('pekerjaan', $vendor->pekerjaan) == 'Logistik' ? 'selected' : '' }}>Logistik</option>
            <option value="Lainnya" {{ old('pekerjaan', $vendor->pekerjaan) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Status *</label>
          <select name="status" required
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
            <option value="active" {{ old('status', $vendor->status) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $vendor->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
      <h2 class="text-sm font-semibold mb-4">Informasi Bank</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Bank</label>
          <input type="text" name="bank" value="{{ old('bank', $vendor->bank) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No. Rekening</label>
          <input type="text" name="no_rekening" value="{{ old('no_rekening', $vendor->no_rekening) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div class="md:col-span-2">
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Pemilik Rekening</label>
          <input type="text" name="nama_rekening" value="{{ old('nama_rekening', $vendor->nama_rekening) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
      <h2 class="text-sm font-semibold mb-4">Kontak & Alamat</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Telepon</label>
          <input type="text" name="telepon" value="{{ old('telepon', $vendor->telepon) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Email</label>
          <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        </div>
        <div class="md:col-span-2">
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Alamat</label>
          <textarea name="alamat" rows="3"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">{{ old('alamat', $vendor->alamat) }}</textarea>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="{{ route('dev.vendors.index') }}"
         class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
        Batal
      </a>
      <button type="submit"
              class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
        Simpan Perubahan
      </button>
    </div>
  </form>
</div>
@endsection
