@extends('layouts.dev')

@section('title', 'Summary RAB - ' . $project->name)

@section('content')
<div class="max-w-5xl mx-auto p-4">
    <h1 class="text-2xl font-bold">Summary RAB — {{ $project->name }}</h1>

    <div class="mt-4 bg-white p-4 rounded shadow">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Internal</th>
                    <th>Total External</th>
                </tr>
            </thead>
            <tbody>
                {{-- simple group by category --}}
                @php
                    $grouped = $project->rabItems->groupBy(function($i){ return $i->masterItem->category ?? 'N/A'; });
                @endphp
                @foreach($grouped as $cat => $items)
                <tr>
                    <td class="border px-2 py-1">{{ $cat }}</td>
                    <td class="border px-2 py-1">@currency($items->sum('total_internal'))</td>
                    <td class="border px-2 py-1">@currency($items->sum('total_external'))</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

