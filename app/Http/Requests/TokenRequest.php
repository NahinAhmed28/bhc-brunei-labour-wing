<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('super-admin', 'administrator') ?? false;
    }

    public function rules(): array
    {
        return ['company_id' => 'required|exists:companies,id', 'agency_id' => 'required|exists:agencies,id', 'token_category_id' => 'required|exists:token_categories,id', 'current_desk_id' => 'nullable|exists:desks,id', 'agent_name' => 'nullable|max:255', 'received_on' => 'required|date', 'demanded_workers' => 'required|integer|min:1|max:10000', 'approved_workers' => 'nullable|integer|min:0|max:10000', 'pre_selected' => 'nullable|boolean', 'amount' => 'nullable|numeric|min:0', 'receipt_number' => 'nullable|max:100', 'bhc_number' => 'nullable|max:100', 'boesl_status' => 'required|in:pending,submitted,returned,not-required', 'boesl_date' => 'nullable|date', 'received_by' => 'nullable|max:255', 'site_visit_required' => 'nullable|boolean', 'site_visit_date' => 'nullable|date', 'site_visit_by' => 'nullable|max:255', 'visa_status' => 'nullable|in:pending,processing,received,rejected', 'file_status' => 'nullable|in:active,on-hold,completed,cancelled', 'remarks' => 'nullable|max:5000', 'change_reason' => 'nullable|max:1000'];
    }
}
