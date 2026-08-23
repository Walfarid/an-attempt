<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Prepare the data for validation — derive the slug from the title
     * when it was not supplied explicitly.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('slug', $this->input('title', ''))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('projects')->ignore($this->route('project')),
            ],
            'description' => ['required', 'string'],
            'year' => ['required', 'integer', 'between:1980,2100'],
            'live_url' => ['nullable', 'string', 'max:255', 'url'],
            'repo_url' => ['nullable', 'string', 'max:255', 'url'],
            'image_tone' => ['nullable', 'string', 'max:255'],
            'featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ];
    }
}
