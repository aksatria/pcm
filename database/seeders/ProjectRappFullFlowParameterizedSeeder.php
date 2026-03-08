<?php

namespace Database\Seeders;

class ProjectRappFullFlowParameterizedSeeder extends Project1Rapp2FullFlowSeeder
{
    protected function targetProjectId(): int
    {
        return (int) env('SEED_PROJECT_ID', 1);
    }

    protected function targetRabId(): int
    {
        return (int) env('SEED_RAB_ID', 2);
    }

    protected function flowCode(): string
    {
        $projectId = $this->targetProjectId();
        $rabId = $this->targetRabId();
        return (string) env('SEED_FLOW_CODE', "P{$projectId}R{$rabId}");
    }
}

