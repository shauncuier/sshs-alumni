<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DonationRequest extends FormRequest
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
        return [
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_email' => ['nullable', 'email', 'max:191'],
            'donor_phone' => ['nullable', 'string', 'max:32'],
            'donor_member_ulid' => ['nullable', 'string', 'exists:members,ulid'],
            'crm_contact_ulid' => ['nullable', 'string', 'exists:crm_contacts,ulid'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'campaign' => ['nullable', 'string', 'max:120'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'is_anonymous' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:2000'],
            'received' => ['required', 'boolean'],
            'method' => ['nullable', 'string', 'max:255'],
        ];
    }
}
