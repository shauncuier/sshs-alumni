<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\CommitteeType;
use Illuminate\Foundation\Http\FormRequest;

class CommitteeRequest extends FormRequest
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
                'name' => ['required', 'string', 'max:120'],
                'type' => ['required', 'string', 'in:'.implode(',', array_column(CommitteeType::cases(), 'value'))],
                'description' => ['nullable', 'string', 'max:2000'],
                'term_start' => ['nullable', 'date'],
                'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            ];
        }

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', array_column(CommitteeType::cases(), 'value'))],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'term_start' => ['sometimes', 'nullable', 'date'],
            'term_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:term_start'],
            'status' => ['sometimes', 'string', 'in:active,archived'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ];
    }
}
