<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\VolunteerStatus;
use Illuminate\Foundation\Http\FormRequest;

class VolunteerRequest extends FormRequest
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
                'phone' => ['nullable', 'string', 'max:32'],
                'email' => ['nullable', 'email', 'max:191'],
                'member_ulid' => ['nullable', 'string', 'exists:members,ulid'],
                'availability' => ['nullable', 'string', 'max:120'],
                'location' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'status' => ['required', 'string', 'in:'.implode(',', array_column(VolunteerStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
