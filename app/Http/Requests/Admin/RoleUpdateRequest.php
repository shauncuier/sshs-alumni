<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            // Each name must exist. Combined with the controller's own
            // whereIn, a forged permission name can neither be assigned nor
            // silently created.
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
