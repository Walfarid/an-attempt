<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\SkillCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('skills')
                    ->where(fn ($query) => $query->where('category', $this->input('category')))
                    ->ignore($this->route('skill')),
            ],
            'category' => ['required', Rule::enum(SkillCategory::class)],
        ];
    }
}
