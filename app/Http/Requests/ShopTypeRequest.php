<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('shopType')?->id ?? null;

        return [
            'name' => 'required|string|max:255|unique:shop_types,name,' . $id,
            'status' => 'required|in:Running,Disabled',
        ];
    }
}
