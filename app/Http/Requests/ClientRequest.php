<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('client') ?? $this->route('id');

        // If this is a PATCH request for partial update (like status only)
        if ($this->method() === 'PATCH') {
            return [
                'status' => [
                    'required',
                    'in:Running,Not Using,Closed,Software Not Urgent,Disappointed,No Operator,Another software choose,Business Closed,Not Happy,Happy'
                ],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],

            'number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'number')->ignore($id),
            ],

            'oparetor_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'oparetor_number')->ignore($id),
            ],

            'project_name' => ['required', 'string', 'max:255'],
            'country_name' => ['nullable','string','max:255'],
            'area_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],

            'referred_by_name' => ['nullable', 'string', 'max:255'],
            'referred_by_number' => ['nullable', 'string', 'max:20'],

            // ✅ accept object and validate name
            'business_type' => ['nullable', 'array'],
            'business_type.name' => ['required', 'string', 'max:255'],

            'status' => ['required', 'in:Running,Not Using,Closed,Software Not Urgent,Disappointed,No Operator,Another software choose,Business Closed,Not Happy,Happy'],
        ];
    }
}
