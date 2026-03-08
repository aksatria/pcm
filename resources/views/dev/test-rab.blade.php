{{-- resources/views/dev/test-rab.blade.php --}}
@extends('layouts.dev')

@section('title', 'Test RAB Model')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">RAB Model Test</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Testing RAB model integration with Project</p>

        @php
            try {
                $project = App\Models\Project::first();
                $rabs = $project ? $project->rabs : collect();
                $currentRab = $project ? $project->currentRab : null;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        @endphp

        @if(isset($error))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <strong>Error:</strong> {{ $error }}
            </div>
        @else
            <!-- Success Message -->
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <strong>Success!</strong> RAB Model is working correctly in Blade views.
            </div>

            <!-- Project Info -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Project Name</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->name }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Project Budget</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->budget_formatted }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total RABs</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $rabs->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- RAB List -->
            @if($rabs->count() > 0)
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">RAB List</h2>
                    <div class="space-y-4">
                        @foreach($rabs as $rab)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $rab->name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Version: {{ $rab->version }} | Items: {{ $rab->items->count() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                                            @if($rab->status == 'approved') bg-emerald-100 text-emerald-800
                                            @elseif($rab->status == 'draft') bg-gray-100 text-gray-800
                                            @elseif($rab->status == 'submitted') bg-blue-100 text-blue-800
                                            @else bg-rose-100 text-rose-800 @endif">
                                            {{ $rab->status_label }}
                                        </span>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                                            Rp {{ number_format($rab->total_budget, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Breakdown -->
                                @php $breakdown = $rab->getBreakdownByCategory(); @endphp
                                @if(count($breakdown) > 0)
                                    <div class="mt-4">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Budget Breakdown:</h4>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            @foreach($breakdown as $category => $data)
                                                <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $category }}</p>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                        Rp {{ number_format($data['total'], 0, ',', '.') }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ number_format($data['percentage'], 1) }}%
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Current RAB Details -->
                @if($currentRab)
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current RAB Details</h2>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentRab->name }}</h3>
                                    <p class="text-gray-600 dark:text-gray-400">Version {{ $currentRab->version }}</p>
                                    <div class="mt-4 space-y-2">
                                        <p><span class="font-semibold">Status:</span> 
                                            <span class="px-2 py-1 text-sm rounded-full 
                                                @if($currentRab->status == 'approved') bg-emerald-100 text-emerald-800
                                                @elseif($currentRab->status == 'draft') bg-gray-100 text-gray-800
                                                @else bg-blue-100 text-blue-800 @endif">
                                                {{ $currentRab->status_label }}
                                            </span>
                                        </p>
                                        <p><span class="font-semibold">Can Edit:</span> 
                                            <span class="{{ $currentRab->canEdit() ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $currentRab->canEdit() ? 'Yes' : 'No' }}
                                            </span>
                                        </p>
                                        <p><span class="font-semibold">Total Items:</span> {{ $currentRab->items->count() }}</p>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Budget</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                        Rp {{ number_format($currentRab->total_budget, 0, ',', '.') }}
                                    </p>
                                    @php $comparison = $currentRab->compareWithProjectBudget(); @endphp
                                    @if($comparison)
                                        <p class="text-sm mt-2 {{ $comparison['is_over_budget'] ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $comparison['is_over_budget'] ? 'Over' : 'Under' }} budget by 
                                            {{ number_format(abs($comparison['percentage']), 1) }}%
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No RABs Found</h3>
                    <p class="text-gray-500 dark:text-gray-400">No RABs have been created for this project yet.</p>
                </div>
            @endif

            <!-- Test Actions -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test Actions</h2>
                <div class="flex space-x-4">
                    <a href="{{ route('dev.test.rab.json') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Test JSON API
                    </a>
                    <a href="{{ route('dev.projects.show', $project) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Back to Project
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
