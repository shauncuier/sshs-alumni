<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MemberLinkType;
use App\Enums\RelationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Per-step validation for the public membership registration.
 *
 * Each step validates only its own fields, so a visitor is never told about a
 * problem four screens away — and the draft in the session stays partial
 * until the final submit.
 *
 * @see docs/05-modules.md section 1
 */
class RegistrationStepRequest extends FormRequest
{
    /**
     * The steps, in order. Public so the controller and the frontend agree on
     * one definition.
     *
     * @var array<int, string>
     */
    public const STEPS = ['basic', 'academic', 'professional', 'location', 'review'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match ($this->route('step')) {
            'basic' => $this->basicRules(),
            'academic' => $this->academicRules(),
            'professional' => $this->professionalRules(),
            'location' => $this->locationRules(),
            'review' => $this->reviewRules(),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function basicRules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'full_name_bn' => ['nullable', 'string', 'max:150'],
            'relation_type' => ['required', Rule::enum(RelationType::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'blood_group' => ['nullable', Rule::enum(BloodGroup::class)],
            'mobile' => ['required', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            // Validated here but never stored in the session draft — see the
            // controller.
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Academic fields are required for former students and optional for
     * everyone else — a former teacher has no SSC year at this school.
     *
     * @return array<string, mixed>
     */
    private function academicRules(): array
    {
        $isStudent = $this->draftRelationType() === RelationType::FormerStudent->value;

        return [
            'batch_id' => [$isStudent ? 'required' : 'nullable', 'integer', 'exists:batches,id'],
            'ssc_year' => [$isStudent ? 'required' : 'nullable', 'integer', 'min:1976', 'max:'.(int) now()->year],
            'student_id' => ['nullable', 'string', 'max:32'],
            'admission_year' => ['nullable', 'integer', 'min:1976', 'max:'.(int) now()->year],
            'group_stream' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:16'],
            'house' => ['nullable', 'string', 'max:32'],
            'higher_education' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function professionalRules(): array
    {
        return [
            'occupation' => ['nullable', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:150'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'industry' => ['nullable', 'string', 'max:80'],
            'business_info' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationRules(): array
    {
        return [
            'country' => ['required', 'string', 'max:80'],
            'division' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewRules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:2000'],
            'bio_bn' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:60'],
            'interests' => ['nullable', 'array', 'max:20'],
            'interests.*' => ['string', 'max:60'],

            'links' => ['nullable', 'array', 'max:6'],
            'links.*.type' => ['required', Rule::enum(MemberLinkType::class)],
            'links.*.url' => ['required', 'url', 'max:255'],

            // Privacy choices are made here, before submitting — not
            // discovered afterwards in a settings screen.
            'privacy' => ['required', 'array'],
            'privacy.show_profile' => ['required', 'boolean'],
            'privacy.show_phone' => ['required', 'boolean'],
            'privacy.show_email' => ['required', 'boolean'],
            'privacy.show_workplace' => ['required', 'boolean'],
            'privacy.show_location' => ['required', 'boolean'],
            'privacy.show_date_of_birth' => ['required', 'boolean'],
            'privacy.show_in_batch_list' => ['required', 'boolean'],

            'terms' => ['accepted'],
        ];
    }

    /**
     * The relation type captured in step one, which decides whether academic
     * fields are required.
     */
    private function draftRelationType(): ?string
    {
        /** @var array<string, mixed> $draft */
        $draft = $this->session()->get('registration.draft', []);

        $value = $draft['relation_type'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Field names as the applicant sees them, so an error reads "পূর্ণ নাম is
     * required" rather than "full name is required".
     *
     * These reuse the form's own labels rather than a partial
     * lang/{locale}/validation.php, which would override Laravel's defaults
     * and break every other validation message.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $fields = [
            'full_name', 'full_name_bn', 'relation_type', 'gender', 'blood_group',
            'date_of_birth', 'mobile', 'whatsapp', 'email', 'password',
            'student_id', 'admission_year', 'group_stream', 'section', 'house',
            'occupation', 'organization', 'job_title', 'industry',
            'country', 'division', 'district', 'city',
            'emergency_contact_name', 'emergency_contact_phone', 'bio',
        ];

        $attributes = ['batch_id' => (string) __('public.join.fields.batch')];

        foreach ($fields as $field) {
            $attributes[$field] = (string) __("public.join.fields.{$field}");
        }

        return $attributes;
    }
}
