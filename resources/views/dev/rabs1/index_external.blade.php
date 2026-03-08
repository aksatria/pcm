@extends('layouts.dev')

@section('title', 'RAB - Penawaran - ' . $project->name)

@section('content')
<div class="max-w-7xl mx-auto p-4">
    <div class="mb-4">
        <h1 class="text-2xl font-bold">RAB (Penawaran) — {{ $project->name }}</h1>
    </div>

    <div class="bg-white shadow rounded p-4">
        <table class="min-w-full table-auto">
            <thead>
                <tr>
                    <th class="px-2 py-1">Code</th>
                    <th class="px-2 py-1">Title</th>
                    <th class="px-2 py-1">Qty</th>
                    <th class="px-2 py-1">Unit</th>
                    <th class="px-2 py-1">Price</th>
                    <th class="px-2 py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                <tr>
                    <td class="border px-2 py-1">{{ $it['code'] }}</td>
                    <td class="border px-2 py-1">{{ $it['title'] }}</td>
                    <td class="border px-2 py-1">{{ $it['qty'] }}</td>
                    <td class="border px-2 py-1">{{ $it['unit'] }}</td>
                    <td class="border px-2 py-1">@currency($it['price'] ?? 0)</td>
                    <td class="border px-2 py-1">@currency($it['total'] ?? 0)</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

