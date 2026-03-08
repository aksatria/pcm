{{-- resources/views/dev/vendors/quick-modal.blade.php --}}
@php
  $projectId = $project->id ?? null;
  $projectName = $project->name ?? 'Proyek';
@endphp

<div id="vendorModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="flex items-center justify-center min-h-screen px-3 py-6">
    <div class="fixed inset-0 bg-black/40 dark:bg-black/60" onclick="closeVendorModal()"></div>
    <div class="relative bg-white dark:bg-gray-900 w-full max-w-xl rounded-xl border border-gray-200 dark:border-gray-800 shadow-2xl">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
        <div>
          <h3 class="text-sm font-semibold">Tambah Vendor</h3>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Vendor baru akan langsung masuk ke pilihan.</p>
        </div>
        <button type="button"
                onclick="closeVendorModal()"
                class="w-7 h-7 inline-flex items-center justify-center rounded-full border border-gray-200 dark:border-gray-800 text-gray-400 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
          ?
        </button>
      </div>

      <form id="vendorQuickForm" class="px-4 py-4 space-y-4">
        <input type="hidden" name="project_id" id="vendor_project_id" value="">
        <div id="vendorError" class="hidden text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Ruang Lingkup</label>
            <div class="flex flex-wrap gap-2">
              <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm cursor-pointer">
                <input type="radio" name="vendor_scope" value="project" checked>
                <span>Proyek ini: {{ $projectName }}</span>
              </label>
              <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm cursor-pointer">
                <input type="radio" name="vendor_scope" value="global">
                <span>Global (semua proyek)</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Vendor *</label>
            <input name="nama" required
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="Contoh: Supplier ABC">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Perusahaan</label>
            <input name="perusahaan"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="PT/CV (opsional)">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Telepon</label>
            <input name="telepon"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="08xxxx">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Email</label>
            <input type="email" name="email"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="email@vendor.com">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Bank</label>
            <input name="bank"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="BCA/BNI (opsional)">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No Rekening</label>
            <input name="no_rekening"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="Nomor rekening">
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Rekening</label>
            <input name="nama_rekening"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                   placeholder="Atas nama rekening">
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Alamat</label>
            <textarea name="alamat" rows="2"
                      class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                      placeholder="Alamat vendor (opsional)"></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button"
                  onclick="closeVendorModal()"
                  class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
            Batal
          </button>
          <button type="submit"
                  class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
            Simpan Vendor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  const vendorProjectId = @json($projectId);
  const vendorProjectName = @json($projectName);

  function openVendorModal() {
    const modal = document.getElementById('vendorModal');
    if (modal) modal.classList.remove('hidden');
    setVendorScope('project');
  }

  function closeVendorModal() {
    const modal = document.getElementById('vendorModal');
    if (modal) modal.classList.add('hidden');
    const err = document.getElementById('vendorError');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
  }

  function setVendorScope(scope) {
    const hidden = document.getElementById('vendor_project_id');
    if (!hidden) return;
    hidden.value = scope === 'project' ? (vendorProjectId || '') : '';
  }

  document.addEventListener('change', function (e) {
    const radio = e.target.closest('input[name="vendor_scope"]');
    if (radio) {
      setVendorScope(radio.value);
    }
  });

  const vendorQuickForm = document.getElementById('vendorQuickForm');
  if (vendorQuickForm) {
    vendorQuickForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const err = document.getElementById('vendorError');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }

      const formData = new FormData(vendorQuickForm);
      try {
        const res = await fetch(`{{ route('dev.vendors.store') }}`, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: formData
        });

        const data = await res.json();
        if (!res.ok || !data.status) {
          const msg = data.message || 'Gagal menyimpan vendor.';
          if (err) {
            err.textContent = msg;
            err.classList.remove('hidden');
          }
          return;
        }

        const vendor = data.vendor || {};
        if (vendor.id) {
          const label = vendor.project_id ? `[Proyek] ${vendor.nama}` : `[Global] ${vendor.nama}`;
          const selects = document.querySelectorAll('[data-vendor-select="1"]');
          selects.forEach(sel => {
            const opt = document.createElement('option');
            opt.value = vendor.id;
            opt.textContent = label;
            if (vendor.bank) opt.dataset.bank = vendor.bank;
            if (vendor.no_rekening) opt.dataset.noRekening = vendor.no_rekening;
            if (vendor.nama_rekening) opt.dataset.namaRekening = vendor.nama_rekening;
            sel.appendChild(opt);
          });

          const inputs = document.querySelectorAll('[data-vendor-input="1"]');
          inputs.forEach(input => {
            input.value = vendor.nama || '';
          });

          const headerSelect = document.querySelector('select[name="vendor_id"][data-vendor-select="1"]');
          if (headerSelect) {
            headerSelect.value = String(vendor.id);
            headerSelect.dispatchEvent(new Event('change'));
          }
        }

        vendorQuickForm.reset();
        closeVendorModal();
      } catch (error) {
        if (err) {
          err.textContent = 'Gagal menyimpan vendor. Coba lagi.';
          err.classList.remove('hidden');
        }
      }
    });
  }
</script>
