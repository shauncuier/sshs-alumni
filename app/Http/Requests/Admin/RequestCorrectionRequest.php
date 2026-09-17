<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('members.verify') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Sent to the member, so it is required — asking for a correction
            // without saying what to correct helps nobody.
            'message' => ['required', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
