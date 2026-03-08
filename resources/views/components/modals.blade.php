<!-- Create/Edit Modal -->
<div x-show="showCreateModal" 
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 overflow-y-auto z-50"
     x-cloak>
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             @click="showCreateModal = false"
             x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
             @click.outside="showCreateModal = false"
             x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <!-- Modal header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-plus mr-2 text-indigo-600"></i>
                    Tambah Item Baru
                </h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Form -->
            <form x-data="itemForm()" @submit.prevent="submitForm()">
                <div class="space-y-4">
                    <!-- Kategori -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select x-model="formData.kategori" 
                                @change="generateKodeItem()"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Kategori</option>
                            <template x-for="kategori in kategoriList" :key="kategori">
                                <option x-text="kategori" :value="kategori"></option>
                            </template>
                        </select>
                        <p class="text-red-500 text-xs mt-1" x-show="errors.kategori" x-text="errors.kategori"></p>
                    </div>

                    <!-- Kode Item -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Kode Item
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   x-model="formData.kode_item"
                                   class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   readonly>
                            <button type="button" 
                                    @click="generateKodeItem()"
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Kode item akan digenerate otomatis</p>
                    </div>

                    <!-- Uraian Item -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Uraian Item <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="formData.uraian_item"
                                  rows="3"
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Deskripsi lengkap item..."></textarea>
                        <p class="text-red-500 text-xs mt-1" x-show="errors.uraian_item" x-text="errors.uraian_item"></p>
                    </div>

                    <!-- Satuan dan Harga Utama -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Satuan Utama <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="formData.satuan_1"
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="kg, m, unit, pcs, dll.">
                            <p class="text-red-500 text-xs mt-1" x-show="errors.satuan_1" x-text="errors.satuan_1"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Harga Satuan Utama <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">Rp</span>
                                </div>
                                <input type="number" 
                                       x-model="formData.harga_satuan_1"
                                       step="0.01"
                                       min="0"
                                       class="w-full pl-12 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="0.00">
                            </div>
                            <p class="text-red-500 text-xs mt-1" x-show="errors.harga_satuan_1" x-text="errors.harga_satuan_1"></p>
                        </div>
                    </div>

                    <!-- Satuan dan Harga Alternatif -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Satuan Alternatif
                            </label>
                            <input type="text" 
                                   x-model="formData.satuan_2"
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Opsional">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Harga Satuan Alternatif
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">Rp</span>
                                </div>
                                <input type="number" 
                                       x-model="formData.harga_satuan_2"
                                       step="0.01"
                                       min="0"
                                       class="w-full pl-12 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Opsional">
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Update -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tanggal Update
                        </label>
                        <input type="datetime-local" 
                               x-model="formData.tanggal_update"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" 
                            @click="showCreateModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                        Simpan Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div x-show="showImportModal" 
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 overflow-y-auto z-50"
     x-cloak>
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             @click="showImportModal = false"
             x-show="showImportModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
             @click.outside="showImportModal = false"
             x-show="showImportModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-file-import mr-2 text-green-600"></i>
                    Import Data dari File
                </h3>
                <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div x-data="importForm()" x-init="init()">
                <!-- Step 1: Upload File -->
                <div x-show="step === 1">
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
                         @drop="handleFileDrop($event)"
                         @dragover.prevent
                         @dragenter.prevent>
                        <input type="file" 
                               id="importFile"
                               @change="handleFileSelect($event)"
                               accept=".xlsx,.xls,.csv"
                               class="hidden">
                        
                        <div class="space-y-3" x-show="!selectedFile">
                            <i class="fas fa-file-excel text-4xl text-green-500"></i>
                            <div>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">Drop file Excel/CSV di sini</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">atau</p>
                            </div>
                            <button type="button" 
                                    @click="document.getElementById('importFile').click()"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Pilih File
                            </button>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Format yang didukung: XLSX, XLS, CSV (max 10MB)
                            </p>
                        </div>

                        <div x-show="selectedFile" class="text-left">
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file-excel text-2xl text-green-500"></i>
                                    <div>
                                        <p class="font-medium text-green-800 dark:text-green-300" x-text="selectedFile.name"></p>
                                        <p class="text-sm text-green-600 dark:text-green-400" x-text="formatFileSize(selectedFile.size)"></p>
                                    </div>
                                </div>
                                <button @click="selectedFile = null" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Download Template -->
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Butuh template?</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">Download template Excel untuk import data</p>
                            </div>
                            <button @click="downloadTemplate()" 
                                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-download mr-1"></i>Download
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Validation Results -->
                <div x-show="step === 2" x-cloak>
                    <div class="space-y-4">
                        <!-- Validation Summary -->
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="validationStats.valid_rows"></p>
                                <p class="text-xs text-green-700 dark:text-green-300">Data Valid</p>
                            </div>
                            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="validationStats.error_rows"></p>
                                <p class="text-xs text-red-700 dark:text-red-300">Error</p>
                            </div>
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="validationStats.total_rows"></p>
                                <p class="text-xs text-blue-700 dark:text-blue-300">Total Data</p>
                            </div>
                        </div>

                        <!-- Error List -->
                        <div x-show="validationErrors.length > 0" x-cloak>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daftar Error:</p>
                            <div class="max-h-40 overflow-y-auto space-y-2">
                                <template x-for="error in validationErrors" :key="error.row">
                                    <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                        <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                            Baris <span x-text="error.row"></span>:
                                        </p>
                                        <ul class="text-xs text-red-700 dark:text-red-400 ml-4 list-disc">
                                            <template x-for="errMsg in error.errors" :key="errMsg">
                                                <li x-text="errMsg"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div x-show="previewData.length > 0" x-cloak>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview Data:</p>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Kode</th>
                                            <th class="px-3 py-2 text-left">Kategori</th>
                                            <th class="px-3 py-2 text-left">Uraian</th>
                                            <th class="px-3 py-2 text-left">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <template x-for="item in previewData" :key="item.kode_item">
                                            <tr>
                                                <td class="px-3 py-2" x-text="item.kode_item"></td>
                                                <td class="px-3 py-2" x-text="item.kategori"></td>
                                                <td class="px-3 py-2" x-text="item.uraian_item"></td>
                                                <td class="px-3 py-2" x-text="formatCurrency(item.harga_satuan_1)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-between">
                    <button type="button" 
                            @click="step > 1 ? step-- : showImportModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span x-text="step > 1 ? 'Kembali' : 'Batal'"></span>
                    </button>
                    
                    <div class="space-x-3" x-show="step === 1">
                        <button type="button" 
                                @click="validateFile()"
                                :disabled="!selectedFile || loading"
                                :class="!selectedFile || loading ? 'opacity-50 cursor-not-allowed' : ''"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                            Validasi File
                        </button>
                    </div>

                    <div class="space-x-3" x-show="step === 2" x-cloak>
                        <button type="button" 
                                @click="importData()"
                                :disabled="validationStats.valid_rows === 0 || loading"
                                :class="validationStats.valid_rows === 0 || loading ? 'opacity-50 cursor-not-allowed' : ''"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                            Import Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form handling untuk create/edit modal
function itemForm() {
    return {
        loading: false,
        formData: {
            kategori: '',
            kode_item: '',
            uraian_item: '',
            satuan_1: '',
            harga_satuan_1: '',
            satuan_2: '',
            harga_satuan_2: '',
            tanggal_update: new Date().toISOString().slice(0, 16)
        },
        errors: {},
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            
            try {
                const response = await fetch('/api/data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showToast('success', 'Berhasil', 'Item berhasil ditambahkan');
                    this.$parent.showCreateModal = false;
                    this.$parent.fetchData();
                    this.$parent.loadStatistics();
                    this.resetForm();
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                    }
                    this.showToast('error', 'Error', result.message || 'Gagal menambah item');
                }
            } catch (error) {
                this.showToast('error', 'Error', 'Terjadi kesalahan saat menyimpan data');
                console.error('Error saving item:', error);
            } finally {
                this.loading = false;
            }
        },
        
        generateKodeItem() {
            if (this.formData.kategori) {
                // Generate kode sementara, nanti akan digenerate oleh backend
                const prefix = this.$parent.kategoriList.find(k => k === this.formData.kategori)?.substring(0, 3) || 'GEN';
                this.formData.kode_item = `${prefix}-XXX`;
            }
        },
        
        resetForm() {
            this.formData = {
                kategori: '',
                kode_item: '',
                uraian_item: '',
                satuan_1: '',
                harga_satuan_1: '',
                satuan_2: '',
                harga_satuan_2: '',
                tanggal_update: new Date().toISOString().slice(0, 16)
            };
            this.errors = {};
        },
        
        showToast(type, title, message) {
            if (this.$parent && this.$parent.showToast) {
                this.$parent.showToast(type, title, message);
            }
        }
    };
}

// Import form handling
function importForm() {
    return {
        step: 1,
        loading: false,
        selectedFile: null,
        validationStats: {},
        validationErrors: [],
        previewData: [],
        
        init() {
            // Initialize component
        },
        
        handleFileDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },
        
        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        },
        
        handleFile(file) {
            // Validasi file type dan size
            const allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
                this.showToast('error', 'Error', 'Format file tidak didukung. Gunakan file Excel atau CSV.');
                return;
            }
            
            if (file.size > maxSize) {
                this.showToast('error', 'Error', 'Ukuran file terlalu besar. Maksimal 10MB.');
                return;
            }
            
            this.selectedFile = file;
        },
        
        async validateFile() {
            if (!this.selectedFile) return;
            
            this.loading = true;
            
            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('import_type', 'validate');
                
                const response = await fetch('/api/data/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.validationStats = result.stats;
                    this.validationErrors = result.errors || [];
                    this.previewData = result.data?.preview || [];
                    this.step = 2;
                } else {
                    this.showToast('error', 'Validasi Gagal', result.message || 'Terjadi kesalahan saat validasi file');
                }
            } catch (error) {
                this.showToast('error', 'Error', 'Terjadi kesalahan saat validasi file');
                console.error('Error validating file:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async importData() {
            this.loading = true;
            
            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('import_type', 'import');
                
                const response = await fetch('/api/data/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showToast('success', 'Import Berhasil', `Data berhasil diimport: ${result.data.created} created, ${result.data.updated} updated`);
                    this.$parent.showImportModal = false;
                    this.$parent.fetchData();
                    this.$parent.loadStatistics();
                    this.resetForm();
                } else {
                    this.showToast('error', 'Import Gagal', result.message || 'Terjadi kesalahan saat import data');
                }
            } catch (error) {
                this.showToast('error', 'Error', 'Terjadi kesalahan saat import data');
                console.error('Error importing data:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async downloadTemplate() {
            try {
                window.open('/api/data/export/template', '_blank');
            } catch (error) {
                this.showToast('error', 'Error', 'Terjadi kesalahan saat mendownload template');
                console.error('Error downloading template:', error);
            }
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        },
        
        resetForm() {
            this.step = 1;
            this.selectedFile = null;
            this.validationStats = {};
            this.validationErrors = [];
            this.previewData = [];
        },
        
        showToast(type, title, message) {
            if (this.$parent && this.$parent.showToast) {
                this.$parent.showToast(type, title, message);
            }
        }
    };
}
</script>
