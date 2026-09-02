<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuideRequest extends FormRequest
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
                Rule::unique('guides')->ignore($this->route('guide')),
            ],
            'body' => ['required', 'string'],
            'teaser' => ['nullable', 'string', 'max:300'],
            'prerequisites' => ['nullable', 'string'],
            'estimated_time' => ['nullable', 'string', 'max:100'],
            'published_at' => ['nullable', 'date'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['integer', 'exists:posts,id'],
        ];
    }
}
