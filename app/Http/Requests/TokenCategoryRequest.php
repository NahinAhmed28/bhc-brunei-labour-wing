<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TokenCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tokenCategory = $this->route('token_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('token_categories', 'name')->ignore($tokenCategory)],
            'code' => ['required', 'string', 'max:50', Rule::unique('token_categories', 'code')->ignore($tokenCategory)],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper($this->string('code')->trim()->toString())]);
    }
}
