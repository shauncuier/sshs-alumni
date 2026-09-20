<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\FaqGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
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
        return [
            'group' => ['required', Rule::in(FaqGroup::values())],
            'question' => ['required', 'string', 'max:300'],
            'answer' => ['required', 'string', 'max:5000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
