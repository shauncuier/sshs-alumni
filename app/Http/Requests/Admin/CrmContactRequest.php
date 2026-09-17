<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\CrmContactType;
use App\Enums\PipelineStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Creating or editing a contact.
 *
 * `member_id` and `last_activity_at` are absent: linking is a deliberate act
 * through PipelineService, and the activity stamp belongs to ActivityLogger.
 *
 * `pipeline_status` and `owner_id` are accepted on CREATE only — the update
 * path strips them, because moving a stage or handing over a relationship are
 * recorded acts rather than form fields.
 */
class CrmContactRequest extends FormRequest
{
    /**
     * Authorization is the controller's, via CrmContactPolicy.
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
            'type' => ['required', new Enum(CrmContactType::class)],
            'name' => ['required', 'string', 'max:150'],
            'organization_name' => ['nullable', 'string', 'max:150'],
            'designation' => ['nullable', 'string', 'max:120'],

            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],

            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],

            'source' => ['nullable', 'string', 'max:80'],
            'relationship_type' => ['nullable', 'string', 'max:80'],
            'pipeline_status' => ['nullable', new Enum(PipelineStage::class)],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],

            // Internal. The subject never reads these.
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
