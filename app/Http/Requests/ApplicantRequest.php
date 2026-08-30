<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('super-admin', 'administrator', 'data-entry') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('applicant')?->id;

        return ['token_id' => 'required|exists:tokens,id', 'full_name' => 'required|max:255', 'father_name' => 'nullable|max:255', 'mother_name' => 'nullable|max:255', 'date_of_birth' => 'nullable|date|before:today', 'gender' => 'nullable|in:Male,Female,Other', 'nationality' => 'required|max:100', 'national_id' => 'nullable|max:100', 'marital_status' => 'nullable|max:50', 'phone' => 'nullable|max:50', 'email' => 'nullable|email|max:255', 'permanent_address' => 'nullable|max:2000', 'present_address' => 'nullable|max:2000', 'passport_number' => 'required|max:100|unique:applicants,passport_number,'.$id.',id,deleted_at,NULL', 'passport_issue_date' => 'nullable|date', 'passport_expiry_date' => 'nullable|date|after:passport_issue_date', 'passport_authority' => 'nullable|max:255', 'job_category' => 'nullable|max:255', 'salary' => 'nullable|numeric|min:0', 'contract_duration' => 'nullable|max:100', 'demand_reference' => 'nullable|max:100', 'applicant_type' => 'nullable|max:100', 'pre_selected' => 'nullable|boolean', 'registration_number' => 'nullable|max:100', 'registration_date' => 'nullable|date', 'tracking_status' => 'required|in:pending,in-progress,complete', 'visa_status' => 'required|in:pending,processing,received,rejected', 'flight_date' => 'nullable|date', 'flight_status' => 'required|in:pending,booked,departed,cancelled', 'insurance_date' => 'nullable|date', 'insurance_status' => 'required|in:pending,received,not-required', 'ic_status' => 'required|in:pending,received,not-required', 'medical_status' => 'required|in:pending,cleared,failed', 'boesl_status' => 'required|in:pending,submitted,returned,not-required', 'remarks' => 'nullable|max:5000', 'override_limit' => 'nullable|boolean', 'override_reason' => 'nullable|required_if:override_limit,1|max:1000'];
    }
}
