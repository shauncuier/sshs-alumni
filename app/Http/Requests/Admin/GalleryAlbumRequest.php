<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GalleryAlbumRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
