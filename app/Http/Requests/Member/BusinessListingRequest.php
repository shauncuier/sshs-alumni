<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class BusinessListingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'alumni_discount' => ['nullable', 'string', 'max:500'],
        ];
    }
}
