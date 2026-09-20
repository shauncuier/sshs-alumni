<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SponsorKind;
use App\Enums\SponsorStatus;
use Illuminate\Foundation\Http\FormRequest;

class SponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'name' => ['required', 'string', 'max:150'],
                'kind' => ['required', 'string', 'in:'.implode(',', array_column(SponsorKind::cases(), 'value'))],
                'sponsorship_package_id' => ['nullable', 'integer', 'exists:sponsorship_packages,id'],
                'event_id' => ['nullable', 'integer', 'exists:events,id'],
                'contact_name' => ['nullable', 'string', 'max:150'],
                'contact_email' => ['nullable', 'email', 'max:191'],
                'contact_phone' => ['nullable', 'string', 'max:32'],
                'website' => ['nullable', 'url', 'max:255'],
                'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'status' => ['sometimes', 'string', 'in:'.implode(',', array_column(SponsorStatus::cases(), 'value'))],
            'is_public' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
