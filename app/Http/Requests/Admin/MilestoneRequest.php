<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MilestoneRequest extends FormRequest
{
    public const EARLIEST_YEAR = 1976;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:'.self::EARLIEST_YEAR, 'max:'.((int) now()->addYear()->year)],
            'date_label' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_highlighted' => ['nullable', 'boolean'],
        ];
    }
}
