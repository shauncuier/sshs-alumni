<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The member's own visibility choices.
 *
 * Every flag is required rather than optional: an omitted checkbox posts
 * nothing, and treating "absent" as "unchanged" would mean a member could
 * never turn a flag OFF.
 */
class PrivacyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->member !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'show_profile' => ['required', 'boolean'],
            'show_phone' => ['required', 'boolean'],
            'show_email' => ['required', 'boolean'],
            'show_workplace' => ['required', 'boolean'],
            'show_location' => ['required', 'boolean'],
            'show_date_of_birth' => ['required', 'boolean'],
            'show_in_batch_list' => ['required', 'boolean'],
        ];
    }
}
