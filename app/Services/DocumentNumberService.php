<?php

namespace App\Services;

use App\Models\DocumentCounter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function nextNumber(int $projectId, string $docType, ?string $projectCode = null, ?Carbon $date = null): string
    {
        $date = $date ?: Carbon::now();
        $docType = strtoupper($docType);
        $projectCode = $projectCode ?: 'PROJ';

        $sequence = DB::transaction(function () use ($projectId, $docType) {
            $counter = DocumentCounter::where('project_id', $projectId)
                ->where('doc_type', $docType)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = DocumentCounter::create([
                    'project_id' => $projectId,
                    'doc_type' => $docType,
                    'last_number' => 0,
                ]);
            }

            $counter->last_number = (int) $counter->last_number + 1;
            $counter->save();

            return $counter->last_number;
        });

        $seq = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $roman = $this->toRoman((int) $date->format('n'));
        $year = $date->format('Y');

        return "{$seq}/{$docType}/{$projectCode}/{$roman}/{$year}";
    }

    private function toRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }
}
