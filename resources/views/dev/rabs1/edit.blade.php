@extends('layouts.dev')

@section('title', 'Edit Item RAB')

@section('content')
<div class="max-w-3xl mx-auto p-4">
    <h1 class="text-xl font-bold">Edit Item RAB</h1>
    <form action="{{ route('dev.rab_items.update', $item) }}" method="POST" class="mt-4 bg-white p-4 rounded shadow">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="input w-full" value="{{ $item->title }}" />
        </div>
        <div class="mb-3">
            <label>Qty</label>
            <input type="number" name="qty" class="input w-full" value="{{ $item->qty }}" />
        </div>
        <div class="mb-3">
            <label>Price Internal</label>
            <input type="number" step="0.01" name="price_internal" class="input w-full" value="{{ $item->price_internal }}" />
        </div>
        <div class="mb-3">
            <button class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
