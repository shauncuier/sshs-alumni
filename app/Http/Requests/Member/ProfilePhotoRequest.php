<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use App\Enums\MediaCollection;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Http\FormRequest;

class ProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->member !== null;
    }

    /**
     * Rules come from MediaService so the form and the storage layer cannot
     * disagree about what is acceptable.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => array_merge(
                ['required'],
                app(MediaService::class)->validationRules(MediaCollection::Profile),
            ),
        ];
    }
}
