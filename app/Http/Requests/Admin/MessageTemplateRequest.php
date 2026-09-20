<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\CampaignChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageTemplateRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'subject_bn' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'body_bn' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
        ];

        if ($this->isMethod('POST')) {
            $rules['key'] = ['nullable', 'string', 'max:80', 'unique:message_templates,key'];
        }

        return $rules;
    }
}
