<?php

namespace App\Http\Requests\Dashboard;

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

                    $clientMime = $value->getClientMimeType();

                    if ($clientMime === 'image/svg+xml') {
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
