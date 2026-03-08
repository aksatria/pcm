<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use Illuminate\Http\Request;

class DocumentShortcutController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('name')->get();

        return view('dev.documents.index', compact('projects'));
    }

    public function select(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->orderByDesc('created_at')->first();

        if (!$rab) {
            return redirect()->route('dev.projects.show', $projectId)
                ->with('warning', 'Project belum memiliki RAPP. Silakan buat RAPP dulu.');
        }

        $doc = $request->get('doc');
        $map = [
            'spp' => 'dev.rab-baseline.spps.index',
            'bpg' => 'dev.rab-baseline.bpgs.index',
            'lpb' => 'dev.rab-baseline.lpbs.index',
            'po'  => 'dev.rab-baseline.purchase-orders.index',
            'spk' => 'dev.rab-baseline.spks.index',
            'komparasi' => 'dev.rab-baseline.vendor-comparisons.index',
            'voucher' => 'dev.rab-baseline.purchase-vouchers.index',
        ];

        if (!isset($map[$doc])) {
            return redirect()->route('dev.documents.index')
                ->with('error', 'Jenis dokumen tidak dikenal.');
        }

        return redirect()->route($map[$doc], [$project->id, $rab->id]);
    }
}



