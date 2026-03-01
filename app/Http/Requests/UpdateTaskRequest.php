<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if you have auth/roles
    }

    public function rules(): array
    {
        return [
            'shop_id' => 'nullable|exists:clients,id',
            'shop_name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'details' => 'required|string',
            'start_date' => 'nullable|date',
            'status' => 'required|in:New,Assigned,Pending,Working,Complete,Cancelled,Reissue,Staff,Future',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ];
    }
}
