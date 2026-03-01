<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // The route parameter is just the ID
        $leadSourceId = $this->route('lead_source'); 

        return [
            'name' => 'required|string|max:255' . $leadSourceId,
            'status' => 'required|in:Running,Disabled',
        ];
    }
}
