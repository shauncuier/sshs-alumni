<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Campaign;
use App\Models\CrmContact;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\User;

class TemplateRenderer
{
    /**
     * Interpolate {key} placeholders in text using the given values.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function render(string $text, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = (string) ($value ?? '');
        }

        return strtr($text, $replacements);
    }

    /**
     * Pick appropriate body based on locale.
     */
    public static function resolveBody(Campaign|MessageTemplate $source, ?string $locale = null): string
    {
        if ($locale === 'bn' && ! empty($source->body_bn)) {
            return $source->body_bn;
        }

        return $source->body;
    }

    /**
     * Pick appropriate subject based on locale.
     */
    public static function resolveSubject(Campaign|MessageTemplate $source, ?string $locale = null): ?string
    {
        if ($locale === 'bn' && ! empty($source->subject_bn)) {
            return $source->subject_bn;
        }

        return $source->subject;
    }

    /**
     * Extract standardized template variables for a recipient.
     *
     * @return array<string, string>
     */
    public static function extractVariables(Member|User|CrmContact|null $recipient): array
    {
        if ($recipient instanceof Member) {
            return [
                'name' => $recipient->full_name,
                'name_bn' => $recipient->full_name_bn ?? $recipient->full_name,
                'membership_no' => $recipient->membership_no ?? '',
                'batch' => $recipient->batch ? (string) $recipient->batch->ssc_year : '',
                'email' => $recipient->email ?? '',
                'phone' => $recipient->mobile ?? '',
                'school' => 'Sabuj Shikshayatan Government High School',
                'association' => 'SSHS Alumni Association',
            ];
        }

        if ($recipient instanceof CrmContact) {
            return [
                'name' => $recipient->full_name,
                'name_bn' => $recipient->full_name,
                'membership_no' => '',
                'batch' => '',
                'email' => $recipient->email ?? '',
                'phone' => $recipient->phone ?? '',
                'school' => 'Sabuj Shikshayatan Government High School',
                'association' => 'SSHS Alumni Association',
            ];
        }

        if ($recipient instanceof User) {
            return [
                'name' => $recipient->name,
                'name_bn' => $recipient->name,
                'membership_no' => $recipient->member?->membership_no ?? '',
                'batch' => $recipient->member?->batch ? (string) $recipient->member->batch->ssc_year : '',
                'email' => $recipient->email,
                'phone' => $recipient->phone ?? '',
                'school' => 'Sabuj Shikshayatan Government High School',
                'association' => 'SSHS Alumni Association',
            ];
        }

        return [
            'name' => 'Member',
            'name_bn' => 'সদস্য',
            'membership_no' => '',
            'batch' => '',
            'email' => '',
            'phone' => '',
            'school' => 'Sabuj Shikshayatan Government High School',
            'association' => 'SSHS Alumni Association',
        ];
    }
}
