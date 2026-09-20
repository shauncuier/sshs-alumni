<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class JobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->member !== null && $this->user()->member->isApproved();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'workplace_type' => ['required', 'string', 'in:on_site,remote,hybrid'],
            'employment_type' => ['required', 'string', 'in:full_time,part_time,contract,internship'],
            'experience_level' => ['nullable', 'string', 'in:entry,mid,senior,lead'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'application_url_or_email' => ['required', 'string', 'max:255'],
            'deadline_at' => ['nullable', 'date'],
        ];
    }
}
