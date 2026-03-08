<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;

class RabBaselineController extends Controller
{
    public function index($projectId)
    {
        $project = Project::with(['client', 'rabs' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($projectId);

        $rabs = $project->rabs;
        $latestApproved = $project->latestApprovedRab()->first();

        return view('dev.rab-baseline.index', [
            'project' => $project,
            'rabs' => $rabs,
            'latestApproved' => $latestApproved,
            'summary' => [
                'count' => $rabs->count(),
                'total' => (float) $rabs->sum('total_budget'),
                'approved' => $rabs->where('status', 'approved')->count(),
                'draft' => $rabs->where('status', 'draft')->count(),
                'submitted' => $rabs->where('status', 'submitted')->count(),
                'rejected' => $rabs->where('status', 'rejected')->count(),
            ],
        ]);
    }
}

