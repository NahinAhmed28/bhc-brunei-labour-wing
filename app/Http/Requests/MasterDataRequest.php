<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('super-admin', 'administrator') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $this->name))]);
        }
    }

    public function rules(): array
    {
        $table = $this->routeIs('companies.*') ? 'companies' : 'agencies';
        $id = $this->route($table === 'companies' ? 'company' : 'agency')?->id;

        return ['name' => ['required', 'max:255', 'regex:/^[^\-.]+$/', 'unique:'.$table.',name,'.$id], 'registration_no' => 'nullable|max:100', 'license_no' => 'nullable|max:100', 'owner_name' => 'nullable|max:255', 'contact_person' => 'nullable|max:255', 'phone' => 'nullable|max:50', 'email' => 'nullable|email|max:255', 'address' => 'nullable|max:2000', 'remarks' => 'nullable|max:2000', 'is_active' => 'nullable|boolean'];
    }

    public function messages(): array
    {
        return ['name.regex' => 'Names may not contain hyphens or dots.'];
    }
}
