<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\MasterData;
use App\Models\MasterItemPrice;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MasterItemPriceController extends Controller
{
    public function index($masterDataId)
    {
        try {
            $masterData = MasterData::with(['prices' => function ($query) {
                $query->with('province')->orderBy('effective_from', 'desc');
            }])->findOrFail($masterDataId);

            $prices = $masterData->prices;
            $provinces = Province::orderBy('name')->get();

            $activePrices = $prices->filter(function ($price) {
                $now = now()->toDateString();
                return (!$price->effective_from || $price->effective_from <= $now)
                    && (!$price->effective_to || $price->effective_to >= $now);
            });

            $priceStats = [
                'total_prices' => $prices->count(),
                'active_prices' => $activePrices->count(),
                'provinces_covered' => $prices->whereNotNull('province_id')->pluck('province_id')->unique()->count(),
            ];

            return view('dev.data.prices.index', [
                'title' => 'Price Management - ' . $masterData->kode_item,
                'subtitle' => 'Kelola harga berdasarkan provinsi dan periode',
                'masterData' => $masterData,
                'prices' => $prices,
                'provinces' => $provinces,
                'priceStats' => $priceStats,
            ]);
        } catch (\Exception $e) {
            Log::error('MasterItemPrice Index Error: ' . $e->getMessage());
            return redirect()->route('dev.data.index')->with('error', 'Data master tidak ditemukan');
        }
    }

    public function store(Request $request, $masterDataId)
    {
        try {
            MasterData::findOrFail($masterDataId);

            $validator = Validator::make($request->all(), [
                'province_id' => 'nullable|exists:provinces,id',
                'price' => 'required|numeric|min:0',
                'effective_from' => 'nullable|date',
                'effective_to' => 'nullable|date|after_or_equal:effective_from',
                'supplier_id' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $existingPrice = $this->checkExistingPrice(
                $masterDataId,
                $request->province_id,
                $request->effective_from,
                $request->effective_to
            );

            if ($existingPrice) {
                $provinceName = $existingPrice->province ? $existingPrice->province->name : 'Global';
                return response()->json([
                    'success' => false,
                    'message' => "Sudah ada harga untuk {$provinceName} pada periode yang sama",
                ], 422);
            }

            DB::beginTransaction();

            $price = MasterItemPrice::create([
                'master_data_id' => $masterDataId,
                'province_id' => $request->province_id,
                'supplier_id' => $request->supplier_id,
                'price' => $request->price,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil ditambahkan',
                'data' => $price->load('province'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterItemPrice Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan harga: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $price = MasterItemPrice::with(['masterData', 'province'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $price,
            ]);
        } catch (\Exception $e) {
            Log::error('MasterItemPrice Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data harga tidak ditemukan',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $price = MasterItemPrice::with('masterData')->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'province_id' => 'nullable|exists:provinces,id',
                'price' => 'required|numeric|min:0',
                'effective_from' => 'nullable|date',
                'effective_to' => 'nullable|date|after_or_equal:effective_from',
                'supplier_id' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $existingPrice = $this->checkExistingPrice(
                $price->master_data_id,
                $request->province_id,
                $request->effective_from,
                $request->effective_to,
                $id
            );

            if ($existingPrice) {
                $provinceName = $existingPrice->province ? $existingPrice->province->name : 'Global';
                return response()->json([
                    'success' => false,
                    'message' => "Sudah ada harga untuk {$provinceName} pada periode yang sama",
                ], 422);
            }

            DB::beginTransaction();

            $price->update([
                'province_id' => $request->province_id,
                'supplier_id' => $request->supplier_id,
                'price' => $request->price,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil diperbarui',
                'data' => $price->load('province'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterItemPrice Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui harga: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $price = MasterItemPrice::findOrFail($id);
            $price->delete();

            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('MasterItemPrice Destroy Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus harga: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function priceComparison($masterDataId)
    {
        try {
            $masterData = MasterData::findOrFail($masterDataId);

            $now = now()->toDateString();
            $prices = MasterItemPrice::where('master_data_id', $masterDataId)
                ->with('province')
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
                })
                ->get();

            $comparison = [];
            $groupedPrices = $prices->groupBy('province_id');

            foreach ($groupedPrices as $provinceId => $priceList) {
                $latestPrice = $priceList->sortByDesc('effective_from')->first();
                $provinceName = $latestPrice->province ? $latestPrice->province->name : 'Global';

                $comparison[] = [
                    'province_id' => $provinceId,
                    'province_name' => $provinceName,
                    'price' => (float) $latestPrice->price,
                    'effective_from' => $latestPrice->effective_from,
                    'price_formatted' => 'Rp ' . number_format($latestPrice->price, 0, ',', '.'),
                    'is_global' => is_null($provinceId),
                    'is_base' => false,
                ];
            }

            $comparison[] = [
                'province_id' => null,
                'province_name' => 'Harga Dasar',
                'price' => (float) $masterData->harga_satuan_1,
                'effective_from' => $masterData->tanggal_update,
                'price_formatted' => 'Rp ' . number_format($masterData->harga_satuan_1, 0, ',', '.'),
                'is_base' => true,
                'is_global' => true,
            ];

            usort($comparison, fn ($a, $b) => $a['price'] <=> $b['price']);

            return response()->json([
                'success' => true,
                'data' => $comparison,
                'master_data' => [
                    'kode_item' => $masterData->kode_item,
                    'uraian_item' => $masterData->uraian_item,
                    'base_price' => $masterData->harga_satuan_1,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MasterItemPrice PriceComparison Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil perbandingan harga: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function checkExistingPrice($masterDataId, $provinceId, $effectiveFrom, $effectiveTo, $excludeId = null)
    {
        $query = MasterItemPrice::where('master_data_id', $masterDataId)
            ->where('province_id', $provinceId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
            $q->where(function ($innerQ) use ($effectiveTo) {
                $innerQ->whereNull('effective_from')->orWhere('effective_from', '<=', $effectiveTo);
            })->where(function ($innerQ) use ($effectiveFrom) {
                $innerQ->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveFrom);
            });
        })->first();
    }
}
