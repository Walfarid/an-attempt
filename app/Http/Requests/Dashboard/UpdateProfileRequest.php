<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'string', 'max:255', 'url'],
            'linkedin_url' => ['nullable', 'string', 'max:255', 'url'],
        ];
    }
}
