<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MentorProfileRequest extends FormRequest
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
            'company_or_institution' => ['required', 'string', 'max:255'],
            'expertise' => ['required', 'array', 'min:1'],
            'expertise.*' => ['required', 'string', 'max:100'],
            'bio' => ['required', 'string', 'max:3000'],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:70'],
            'max_mentees' => ['required', 'integer', 'min:1', 'max:20'],
            'is_available' => ['required', 'boolean'],
        ];
    }
}
