<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'shop_type' => 'nullable|string|max:255',
            'country_name' => 'nullable|string|max:255',
            'locations' => 'nullable|string|max:255',

            'lead_source' => 'nullable|string|max:255',
            'interest_level' => 'nullable|string|max:255',
            'service_type' => 'nullable|array',
            'service_type.*' => 'string',

            'feature_need' => 'nullable|string',
            'our_commitment' => 'nullable|string',

            'offer_connect' => 'nullable|string|max:255',
            'client_behaviour' => 'nullable|string',
            'status' => 'nullable|string',
            'staff_status' => 'nullable|string',

            'last_contact_date' => 'nullable|date',
            'next_follow_up_date' => 'nullable|date',
            'last_discuss_note' => 'nullable|string',

            'created_by' => 'required|integer',

            'numbers' => 'required|array|min:1',
            'numbers.*.number' => 'required|string',
            'numbers.*.full_number' => 'required|string',
            'numbers.*.type' => 'nullable|string',
            'numbers.*.country_code' => 'nullable|string|max:10',
        ];
    }
}
