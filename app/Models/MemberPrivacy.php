<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MemberPrivacyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-field visibility flags, one row per member.
 *
 * Defaults are privacy-preserving: contact details are hidden unless the member
 * opts in. Enforcement happens in the API Resource layer — a private field is
 * absent from the payload, never blanked or CSS-hidden.
 *
 * @see docs/08-security-privacy.md section 2
 *
 * @property int $id
 * @property int $member_id
 * @property bool $show_profile
 * @property bool $show_phone
 * @property bool $show_email
 * @property bool $show_workplace
 * @property bool $show_location
 * @property bool $show_date_of_birth
 * @property bool $show_in_batch_list
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'member_id', 'show_profile', 'show_phone', 'show_email', 'show_workplace', 'show_location',
    'show_date_of_birth', 'show_in_batch_list',
])]
class MemberPrivacy extends Model
{
    /** @use HasFactory<MemberPrivacyFactory> */
    use HasFactory;

    protected $table = 'member_privacy';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_profile' => 'boolean',
            'show_phone' => 'boolean',
            'show_email' => 'boolean',
            'show_workplace' => 'boolean',
            'show_location' => 'boolean',
            'show_date_of_birth' => 'boolean',
            'show_in_batch_list' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
