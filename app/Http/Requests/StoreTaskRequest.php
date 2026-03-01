<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        // modify if you want permission checks
        return true;
    }

    public function rules()
    {
        return [
            'shop_id' => 'nullable|exists:clients,id',
            'shop_name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'details' => ['nullable', 'string'],
            'status' => 'required|in:New,Assigned,Pending,Working,Complete,Cancelled,Reissue,Staff,Future',
            'start_date' => 'nullable|date',
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
