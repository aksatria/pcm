<?php
// app/Http/Controllers/Dev/RabTestController.php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use Illuminate\Http\Request;

class RabTestController extends Controller
{
    public function testJson()
    {
        try {
            $results = [];
            
            // Test 1: Direct Rab model access
            $results['rab_count'] = Rab::count();
            $results['all_rabs'] = Rab::all()->map(function($rab) {
                return [
                    'id' => $rab->id,
                    'name' => $rab->name,
                    'project_id' => $rab->project_id,
                    'total_budget' => $rab->total_budget,
                    'status' => $rab->status,
                    'status_label' => $rab->status_label,
                    'items_count' => $rab->items->count()
                ];
            });
            
            // Test 2: Through Project
            $project = Project::first();
            $results['project'] = $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'budget' => $project->budget
            ] : null;
            
            if ($project) {
                $results['project_rabs_count'] = $project->rabs->count();
                $results['project_rabs'] = $project->rabs->map(function($rab) {
                    return [
                        'id' => $rab->id,
                        'name' => $rab->name,
                        'total_budget' => $rab->total_budget,
                        'status' => $rab->status_label,
                        'breakdown' => $rab->getBreakdownByCategory(),
                        'items_count' => $rab->items->count()
                    ];
                });
                
                if ($project->rabs->count() > 0) {
                    $rab = $project->rabs->first();
                    $results['first_rab_details'] = [
                        'name' => $rab->name,
                        'budget' => $rab->total_budget,
                        'status' => $rab->status_label,
                        'status_color' => $rab->status_color,
                        'can_edit' => $rab->canEdit(),
                        'breakdown' => $rab->getBreakdownByCategory(),
                        'items_count' => $rab->items->count(),
                        'version' => $rab->version
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'RAB Model Test Successful - All systems working!',
                'data' => $results,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'RAB Model Test Failed',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function testView()
    {
        try {
            $project = Project::first();
            $rabs = $project ? $project->rabs : collect();
            $currentRab = $project ? $project->currentRab : null;
            
            return view('dev.test-rab', compact('project', 'rabs', 'currentRab'));
            
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
        }
    }
}