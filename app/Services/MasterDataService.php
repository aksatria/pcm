<?php

namespace App\Services;

use App\Models\MasterData;
use App\Models\MasterCodeCounter;
use App\Models\MasterDataAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MasterDataService
{
    public function generateCode($category)
    {
        return DB::transaction(function () use ($category) {
            $counter = MasterCodeCounter::where('category', $category)->lockForUpdate()->first();
            
            if (!$counter) {
                $counter = MasterCodeCounter::create([
                    'category' => $category,
                    'last_number' => 0
                ]);
            }

            $counter->increment('last_number');
            $nextNumber = str_pad($counter->last_number, 3, '0', STR_PAD_LEFT);

            return "{$category}.{$nextNumber}";
        });
    }

    public function createItem($data)
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['code'])) {
                $data['code'] = $this->generateCode($data['category']);
            }

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $masterData = MasterData::create($data);

            $this->logAudit($masterData, 'created', null, $masterData->toArray());

            return $masterData;
        });
    }

    public function updateItem($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $masterData = MasterData::findOrFail($id);
            $oldData = $masterData->toArray();

            $data['updated_by'] = Auth::id();
            $masterData->update($data);

            $this->logAudit($masterData, 'updated', $oldData, $masterData->toArray());

            return $masterData;
        });
    }

    public function deleteItem($id)
    {
        return DB::transaction(function () use ($id) {
            $masterData = MasterData::findOrFail($id);
            $oldData = $masterData->toArray();

            $masterData->delete();

            // Nonaktifkan audit trail sementara untuk menghindari error
            // $this->logAudit($masterData, 'deleted', $oldData, null);

            return true;
        });
    }

    public function bulkDelete($ids)
    {
        return DB::transaction(function () use ($ids) {
            $items = MasterData::whereIn('id', $ids)->get();
            $count = 0;

            foreach ($items as $item) {
                $item->delete();
                $count++;
            }

            return $count;
        });
    }

    private function logAudit($masterData, $action, $oldData, $newData)
    {
        try {
            MasterDataAudit::create([
                'master_data_id' => $masterData->id,
                'action' => $action,
                'old_data' => $oldData,
                'new_data' => $newData,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            \Log::error('Audit logging failed: ' . $e->getMessage());
            // Continue without audit logging
        }
    }

    public function getCategoryCounts()
    {
        return MasterData::active()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    public function applyFilters($query, Request $request)
    {
        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        // Price range filter
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Date range filter
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Created by filter
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        return $query;
    }

    public function getSorting($request)
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['code', 'name', 'category', 'unit', 'price', 'created_at', 'updated_at'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return [$sortField, $sortDirection];
    }
}