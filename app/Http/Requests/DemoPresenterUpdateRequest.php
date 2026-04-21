<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemoPresenterUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'designation' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:5'],
            'status' => ['required', 'in:Running,Suspend,Disable'],
        ];
    }
}
