@extends('layouts.dev')

@section('title', 'RAB Item - ' . ($item->title ?? 'Detail'))

@section('content')
<div class="max-w-3xl mx-auto p-4">
    <h1 class="text-xl font-bold">Item: {{ $item->title }}</h1>
    <div class="mt-4 bg-white p-4 rounded shadow">
        <p><strong>Code:</strong> {{ $item->code }}</p>
        <p><strong>Unit:</strong> {{ $item->unit }}</p>
        <p><strong>Qty:</strong> {{ $item->qty }}</p>
        <p><strong>Price Internal:</strong> @currency($item->price_internal)</p>
        <p><strong>Price External:</strong> @currency($item->price_external)</p>
        <p><strong>Status:</strong> {{ $item->status }}</p>
    </div>
</div>
@endsection
