<?php

namespace App\Http\Requests\Dashboard;

use App\Support\SvgSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class StoreMediaRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('The :attribute field must be an image.');

                        return;
                    }

                    // Whitelist allowed extensions to block polyglot uploads
                    // (e.g. shell.php carrying an image/svg+xml MIME).
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'];
                    $ext = strtolower($value->getClientOriginalExtension());

                    if (! in_array($ext, $allowedExtensions, true)) {
                        $fail('The :attribute field must be an image.');

                        return;
                    }

                    $clientMime = $value->getClientMimeType();

                    if ($clientMime === 'image/svg+xml') {
                        $svgContent = file_get_contents($value->getPathname());
                        $sanitized = SvgSanitizer::sanitize($svgContent ?: '');

                        if ($sanitized === '') {
                            $fail('The :attribute field must be a valid SVG image.');
                        }

                        return;
                    }

                    $validator = Validator::make(
                        [$attribute => $value],
                        [$attribute => 'image'],
                    );

                    if ($validator->fails()) {
                        $fail('The :attribute field must be an image.');
                    }
                },
                'max:4096',
            ],
        ];
    }
}
