<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\Member;

/**
 * Scores how complete a member profile is, 0–100.
 *
 * Weighted by what the association actually needs rather than by field count:
 * a photo and contact details matter more to a directory than a house name
 * does, so filling in trivia cannot inflate the score.
 *
 * @see docs/05-modules.md section 1
 */
class ProfileCompletionCalculator
{
    /**
     * Weighted groups. Each group scores the proportion of its fields present,
     * then contributes that proportion of its weight. Weights total 100.
     *
     * @var array<string, array{weight: int, fields: array<int, string>}>
     */
    private const GROUPS = [
        'identity' => [
            'weight' => 20,
            'fields' => ['full_name', 'full_name_bn', 'date_of_birth', 'gender'],
        ],
        'photo' => [
            'weight' => 15,
            'fields' => ['photo_path'],
        ],
        'contact' => [
            'weight' => 20,
            'fields' => ['mobile', 'email'],
        ],
        'academic' => [
            'weight' => 20,
            'fields' => ['batch_id', 'ssc_year', 'group_stream'],
        ],
        'professional' => [
            'weight' => 10,
            'fields' => ['occupation', 'organization'],
        ],
        'location' => [
            'weight' => 10,
            'fields' => ['district', 'city'],
        ],
        'about' => [
            'weight' => 5,
            'fields' => ['bio'],
        ],
    ];

    public function calculate(Member $member): int
    {
        $score = 0.0;

        foreach (self::GROUPS as $group) {
            $present = 0;

            foreach ($group['fields'] as $field) {
                if (filled($member->getAttribute($field))) {
                    $present++;
                }
            }

            $score += ($present / count($group['fields'])) * $group['weight'];
        }

        return (int) round(min(100, $score));
    }

    /**
     * Which groups are still incomplete, so the member dashboard can say what
     * to fill in rather than just showing a percentage.
     *
     * @return array<int, string>
     */
    public function missingGroups(Member $member): array
    {
        $missing = [];

        foreach (self::GROUPS as $name => $group) {
            foreach ($group['fields'] as $field) {
                if (blank($member->getAttribute($field))) {
                    $missing[] = $name;

                    continue 2;
                }
            }
        }

        return $missing;
    }
}
