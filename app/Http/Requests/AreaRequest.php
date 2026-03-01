<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AreaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id');

        return [
            'area_name' => 'required|unique:areas,area_name,' . $id,
            'status' => 'required|in:Running,Disabled',
            'country_name' => 'required|exists:countries,country_name',
        ];
    }
}
