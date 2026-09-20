<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'payable_kind' => ['required', 'string', 'in:fee,registration,donation,sponsor'],
            'payable_id' => ['required', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
