<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'subject' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->channel === CampaignChannel::Mail->value)],
            'subject_bn' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'body_bn' => ['nullable', 'string'],
            'audience_type' => ['required', Rule::enum(AudienceType::class)],
            'audience_filters' => ['nullable', 'array'],
            'message_template_id' => ['nullable', 'exists:message_templates,id'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
