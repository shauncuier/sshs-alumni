<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberTransitionRequest extends FormRequest
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
            'status' => ['required', Rule::enum(MemberStatus::class)],
            // Internal. Never shown to the member.
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
