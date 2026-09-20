<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MentorshipInquiryRequest extends FormRequest
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
            'mentor_id' => ['required', 'integer', 'exists:members,id'],
            'topic' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
