<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreTokenDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('super-admin', 'administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:confirmation-letter,demand-letter'],
            'file' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->extensions(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max(10 * 1024),
            ],
        ];
    }
}
