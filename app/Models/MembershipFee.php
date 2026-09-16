<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use Carbon\CarbonImmutable;
use Database\Factories\MembershipFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A fee for one member for one period.
 *
 * A fee can be WAIVED with a reason. Volunteer-run associations routinely waive
 * fees, and recording that as a fake payment would corrupt the ledger.
 *
 * @property int $id
 * @property int $member_id
 * @property string $period_label
 * @property string $amount
 * @property string $currency
 * @property CarbonImmutable|null $due_at
 * @property FeeStatus $status
 * @property bool $waived
 * @property string|null $waived_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'member_id', 'period_label', 'amount', 'currency', 'due_at', 'status', 'waived', 'waived_reason',
])]
class MembershipFee extends Model
{
    /** @use HasFactory<MembershipFeeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FeeStatus::class,
            'amount' => 'decimal:2',
            'due_at' => 'date',
            'waived' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
