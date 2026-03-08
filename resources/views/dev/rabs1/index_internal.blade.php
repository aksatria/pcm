@extends('layouts.dev')

@section('title', 'RAB - Internal - ' . $project->name)

@section('content')
<div class="max-w-7xl mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">RAB (Internal) — {{ $project->name }}</h1>
        <div class="space-x-2">
            <form method="POST" action="{{ route('dev.rabs.generateDraft', $project) }}" class="inline">
                @csrf
                <button class="btn btn-primary">Generate Draft dari Master</button>
            </form>
            @can('approve', $project)
            <form method="POST" action="{{ route('dev.rabs.approve', $project) }}" class="inline">
                @csrf
                <input type="number" name="markup_percent" placeholder="Markup %" class="input" style="width:100px"/>
                <button class="btn btn-success">Approve & Freeze</button>
            </form>
            @endcan
        </div>
    </div>

    <div class="bg-white shadow rounded p-4">
        <table class="min-w-full table-auto">
            <thead>
                <tr>
                    <th class="px-2 py-1">Code</th>
                    <th class="px-2 py-1">Title</th>
                    <th class="px-2 py-1">Qty</th>
                    <th class="px-2 py-1">Unit</th>
                    <th class="px-2 py-1">Price Internal</th>
                    <th class="px-2 py-1">Price External</th>
                    <th class="px-2 py-1">Total Internal</th>
                    <th class="px-2 py-1">Total External</th>
                    <th class="px-2 py-1">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                <tr>
                    <td class="border px-2 py-1">{{ $it->code }}</td>
                    <td class="border px-2 py-1">{{ $it->title }}</td>
                    <td class="border px-2 py-1">{{ $it->qty }}</td>
                    <td class="border px-2 py-1">{{ $it->unit }}</td>
                    <td class="border px-2 py-1">@currency($it->price_internal ?? 0)</td>
                    <td class="border px-2 py-1">@currency($it->price_external ?? 0)</td>
                    <td class="border px-2 py-1">@currency($it->total_internal ?? 0)</td>
                    <td class="border px-2 py-1">@currency($it->total_external ?? 0)</td>
                    <td class="border px-2 py-1">{{ ucfirst($it->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

