<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateRabBreakdownCodes('WBS-', 'RAB-');
        $this->migrateRabBreakdownCodes('WBS-SEED-', 'RAB-SEED-');

        DB::table('projects')
            ->where('code', 'like', 'PROJ-WBS-%')
            ->update([
                'code' => DB::raw("replace(code, 'PROJ-WBS-', 'PROJ-RAB-')"),
            ]);
    }

    public function down(): void
    {
        $this->migrateRabBreakdownCodes('RAB-', 'WBS-');
        $this->migrateRabBreakdownCodes('RAB-SEED-', 'WBS-SEED-');

        DB::table('projects')
            ->where('code', 'like', 'PROJ-RAB-%')
            ->update([
                'code' => DB::raw("replace(code, 'PROJ-RAB-', 'PROJ-WBS-')"),
            ]);
    }

    private function migrateRabBreakdownCodes(string $fromPrefix, string $toPrefix): void
    {
        $rows = DB::table('rab_breakdowns')
            ->select('id', 'project_id', 'rab_breakdown_code')
            ->where('rab_breakdown_code', 'like', $fromPrefix . '%')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $oldCode = (string) $row->rab_breakdown_code;
            $newCode = $toPrefix . substr($oldCode, strlen($fromPrefix));

            $conflictExists = DB::table('rab_breakdowns')
                ->where('project_id', $row->project_id)
                ->where('rab_breakdown_code', $newCode)
                ->where('id', '!=', $row->id)
                ->exists();

            if ($conflictExists) {
                continue;
            }

            DB::table('rab_breakdowns')
                ->where('id', $row->id)
                ->update(['rab_breakdown_code' => $newCode]);
        }
    }
};

