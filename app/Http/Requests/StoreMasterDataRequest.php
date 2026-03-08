<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterDataRequest extends FormRequest
{
    public function authorize()
    {
        // ubah sesuai kebijakan authorisasi kamu; default true untuk development
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            // biarkan code nullable — jika kosong, controller akan meng-generate otomatis
            'code' => ['nullable', 'string', 'max:191', 'unique:master_data,code'],
            'category' => ['required', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'price' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            // tambahkan rules lain sesuai kebutuhan
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama harus diisi.',
            'code.unique' => 'Kode sudah ada. Silakan gunakan kode lain atau biarkan kosong agar otomatis dibuat.',
            'category.required' => 'Kategori harus dipilih.',
        ];
    }
}
