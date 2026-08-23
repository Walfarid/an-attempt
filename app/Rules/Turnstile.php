<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Turnstile implements ValidationRule
{
    /**
     * Verify a Cloudflare Turnstile token against the siteverify API.
     *
     * When no secret key is configured (local development) the check is
     * skipped; production must always set TURNSTILE_SECRET_KEY.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = (string) config('contact.turnstile_secret_key');

        if ($secret === '') {
            return;
        }

        $response = Http::asForm()->timeout(10)->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => $secret,
                'response' => (string) $value,
                'remoteip' => request()->ip(),
            ],
        );

        if ($response->json('success') !== true) {
            $fail(__('The captcha verification failed. Please try again.'));
        }
    }
}
