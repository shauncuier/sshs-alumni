<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMembershipNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('members.verify') ?? false;
    }

    /**
     * The bound member, or null when the route parameter has not resolved.
     */
    private function member(): ?Member
    {
        $member = $this->route('member');

        return $member instanceof Member ? $member : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Null means "generate one". A supplied value must be unique,
            // because two members sharing a number defeats the point of it.
            'membership_no' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('members', 'membership_no')
                    ->ignore($this->member()?->id),
            ],
        ];
    }
}
