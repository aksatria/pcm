@extends('layouts.dev')

@section('title', 'Tambah Item RAB')

@section('content')
<div class="max-w-3xl mx-auto p-4">
    <h1 class="text-xl font-bold">Tambah Item RAB</h1>
    <form action="{{ route('dev.rab_items.store') }}" method="POST" class="mt-4 bg-white p-4 rounded shadow">
        @csrf
        <div class="mb-3">
            <label class="block">Master Item</label>
            <select name="master_item_id" class="input w-full">
                @foreach(\App\Models\MasterItem::all() as $mi)
                    <option value="{{ $mi->id }}">{{ $mi->code }} - {{ $mi->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Qty</label>
            <input type="number" name="qty" class="input w-full" value="0" />
        </div>
        <div class="mb-3">
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
