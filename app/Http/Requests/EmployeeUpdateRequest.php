<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeUpdateRequest extends FormRequest
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
            'email' => ['nullable', 'email'], // Unique removed
            'designation' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:5'],
            'status' => ['required', 'in:Running,Suspend,Disable'],
        ];
    }
}
