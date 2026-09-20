<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use Carbon\CarbonImmutable;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A verifiable digital certificate issued to an alumnus or volunteer.
 *
 * @property int $id
 * @property string $ulid
 * @property string $certificate_no
 * @property int|null $member_id
 * @property string $recipient_name
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property int|null $event_id
 * @property CarbonImmutable $issue_date
 * @property string $qr_token
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'certificate_no', 'member_id', 'recipient_name', 'title',
    'description', 'type', 'event_id', 'issue_date', 'qr_token', 'metadata',
])]
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $certificate): void {
            if (blank($certificate->qr_token)) {
                $certificate->qr_token = Str::random(40);
            }
            if (blank($certificate->certificate_no)) {
                $certificate->certificate_no = 'SSHS-CERT-'.date('Y').'-'.strtoupper(Str::random(6));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'metadata' => 'array',
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
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
