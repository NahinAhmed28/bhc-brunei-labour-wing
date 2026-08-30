<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('super-admin', 'administrator') ?? false;
    }

    public function rules(): array
    {
        // demanded_workers and required_visa_attestation are mutually exclusive depending
        // on category. Both are accepted as nullable here; the controller enforces that
        // exactly one is filled based on the selected category's code.
        return [
            'company_id' => 'required|exists:companies,id',
            'agency_id' => 'required|exists:agencies,id',
            'token_category_id' => 'required|exists:token_categories,id',
            'current_holder_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'agent_name' => 'nullable|max:255',
            'received_on' => 'required|date',
            'demanded_workers' => 'nullable|integer|min:1|max:10000',
            'required_visa_attestation' => 'nullable|integer|min:1|max:10000',
            'approved_workers' => 'nullable|integer|min:0|max:10000',
            'pre_selected' => 'nullable|boolean',
            'bhc_number' => 'nullable|max:100',
            'boesl_status' => 'required|in:pending,submitted,returned,not-required',
            'boesl_date' => 'nullable|date',
            'site_visit_required' => 'nullable|boolean',
            'site_visit_date' => 'nullable|date',
            'site_visit_by' => 'nullable|max:255',
            'visa_status' => 'nullable|in:pending,processing,received,rejected',
            'file_status' => 'nullable|in:active,on-hold,completed,cancelled',
            'remarks' => 'nullable|max:5000',
            'change_reason' => 'nullable|max:1000',
        ];
    }
}
