<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:New,Assigned,Pending,Working,Complete,Cancelled,Reissue,Staff,Future',
            'reissue_comment' => 'nullable|string',
            'complete_note' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'committed_hours' => 'nullable|integer|min:1', // ✅ add this
        ];
    }
}
