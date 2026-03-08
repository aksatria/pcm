<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterDataRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category' => 'sometimes|required|in:MT,JS,AT,HO,SR,SB',
            'name' => 'sometimes|required|string|max:255|unique:master_data,name,' . $this->route('id') . ',id,category,' . $this->category . ',deleted_at,NULL',
            'unit' => 'sometimes|required|string|max:50',
            'price' => 'sometimes|required|numeric|min:0|max:9999999999.99',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ];
    }
}