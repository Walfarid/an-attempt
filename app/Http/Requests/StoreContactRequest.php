<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Normalise third-party field names before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'turnstile_token' => $this->input('cf-turnstile-response'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The `website` field is a honeypot — humans never see it, so any
     * value means a bot filled the form.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'website' => ['prohibited'],
            // Nullable here: when a secret key IS configured, an absent
            // token fails inside the rule (siteverify rejects it); when
            // none is configured (local dev) the whole check is skipped.
            'turnstile_token' => ['nullable', new Turnstile],
        ];
    }
}
