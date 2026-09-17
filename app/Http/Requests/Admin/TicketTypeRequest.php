<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A ticket type.
 *
 * `sold_count` and `currency` are absent: the count belongs to the registrar
 * and the currency follows the event, so neither can arrive in a form post.
 */
class TicketTypeRequest extends FormRequest
{
    /**
     * Authorization is the controller's, via EventPolicy.
     */
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
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            // null means unlimited, which is different from zero.
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_person_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'per_person_limit' => $this->input('per_person_limit') ?? 1,
        ]);
    }
}
