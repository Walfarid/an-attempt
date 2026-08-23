<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PublicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'citation' => ['required', 'string'],
            'venue' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'digits:4', 'min:1900', 'max:'.(now()->year + 1)],
            'doi_url' => ['required', 'url', 'max:255'],
        ];
    }
}
