<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HeaderLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // You can add permission logic here
    }

    public function rules(): array
    {
        return [
            'image' => $this->isMethod('POST')
                ? 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
