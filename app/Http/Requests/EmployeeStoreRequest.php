<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'designation' => 'nullable|string|max:255',
            'password' => 'required|string|min:5',
            'status' => 'nullable|in:Running,Suspend,Disable',
        ];
    }
}
