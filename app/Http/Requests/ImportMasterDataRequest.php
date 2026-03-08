<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMasterDataRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'File import wajib diupload',
            'file.mimes' => 'File harus berupa Excel (xlsx, xls) atau CSV',
            'file.max' => 'File tidak boleh lebih dari 10MB'
        ];
    }
}