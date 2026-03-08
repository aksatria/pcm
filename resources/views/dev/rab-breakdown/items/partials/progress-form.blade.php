<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Update Progress</h3>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            Last updated: {{ $lastUpdated }}
        </span>
    </div>
    
    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
            <span>Progress</span>
            <span class="font-semibold">{{ number_format($progressPercentage, 1) }}%</span>
        </div>
        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-blue-600 rounded-full transition-all duration-500 ease-out"
                 style="width: {{ $progressPercentage }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
            <span>0%</span>
            <span>100%</span>
        </div>
    </div>
    
    <!-- Budget vs Actual -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">Budget</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">
                Rp {{ number_format($budgetAmount, 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-green-800 dark:text-green-300 mb-1">Actual</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-200">
                Rp {{ number_format($actualAmount, 0, ',', '.') }}
            </div>
        </div>
    </div>
    
    <!-- Update Form -->
    <form action="{{ $updateUrl }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="space-y-4">
            <div>
                <label for="actual_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Actual Amount Spent
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">Rp</span>
                    </div>
                    <input type="number" 
                           name="actual_amount" 
                           id="actual_amount"
                           value="{{ old('actual_amount', $actualAmount) }}"
                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-12 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                           placeholder="0"
                           min="0"
                           step="1000">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">IDR</span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Masukkan jumlah yang sudah dikeluarkan untuk RAB Breakdown ini.
                </p>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                </label>
                <select name="status" 
                        id="status"
                        class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                    <option value="not_started" {{ $status == 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
                    <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>Dalam Progress</option>
                    <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="delayed" {{ $status == 'delayed' ? 'selected' : '' }}>Terlambat</option>
                </select>
            </div>
            
            <div>
                <label for="progress_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Catatan Progress (Opsional)
                </label>
                <textarea name="progress_notes" 
                          id="progress_notes"
                          rows="3"
                          class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                          placeholder="Tambahkan catatan tentang progress...">{{ old('progress_notes', $progressNotes) }}</textarea>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Update Progress
            </button>
        </div>
    </form>
</div>
