<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MemberLinkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What a member may change about themselves.
 *
 * `status`, `membership_no`, `verified_at`, `verified_by`, `relation_type` and
 * `profile_completion` are ABSENT from these rules on purpose. Those belong to
 * the committee, and validated() only ever returns what is listed here — so a
 * forged field in the payload is discarded rather than written.
 *
 * @see docs/08-security-privacy.md section 12
 */
class ProfileUpdateRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:150'],
            'full_name_bn' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'blood_group' => ['nullable', Rule::enum(BloodGroup::class)],

            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'ssc_year' => ['nullable', 'integer', 'min:1976', 'max:'.(int) now()->year],
            'student_id' => ['nullable', 'string', 'max:32'],
            'admission_year' => ['nullable', 'integer', 'min:1976', 'max:'.(int) now()->year],
            'group_stream' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:16'],
            'house' => ['nullable', 'string', 'max:32'],
            'higher_education' => ['nullable', 'string', 'max:255'],

            'occupation' => ['nullable', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:150'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'industry' => ['nullable', 'string', 'max:80'],
            'business_info' => ['nullable', 'string', 'max:2000'],

            'country' => ['nullable', 'string', 'max:80'],
            'division' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:500'],

            'mobile' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:191'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],

            'bio' => ['nullable', 'string', 'max:2000'],
            'bio_bn' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:60'],
            'interests' => ['nullable', 'array', 'max:20'],
            'interests.*' => ['string', 'max:60'],

            'links' => ['nullable', 'array', 'max:6'],
            'links.*.type' => ['required', Rule::enum(MemberLinkType::class)],
            'links.*.url' => ['required', 'url', 'max:255'],
        ];
    }
}
