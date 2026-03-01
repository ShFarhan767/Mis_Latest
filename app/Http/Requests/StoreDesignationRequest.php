<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id') ?? null; // for unique validation on update
        return [
            'designation_name' => 'required|string|max:255|unique:designations,designation_name,' . $id,
            'status' => 'required|in:Running,Disabled',
        ];
    }
}
