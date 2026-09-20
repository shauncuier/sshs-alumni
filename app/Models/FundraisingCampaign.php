<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Concerns\Publishable;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\FundraisingCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A dedicated fundraising campaign with goal, progress tracking, and donor recognition.
 *
 * @property int $id
 * @property string $ulid
 * @property string $slug
 * @property string $title
 * @property string|null $tagline
 * @property string $description
 * @property float $goal_amount
 * @property float $raised_amount
 * @property string|null $cover_image_path
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property bool $is_featured
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'slug', 'title', 'tagline', 'description', 'goal_amount',
    'raised_amount', 'cover_image_path', 'starts_at', 'ends_at',
    'is_featured', 'status', 'published_at',
])]
class FundraisingCampaign extends Model
{
    /** @use HasFactory<FundraisingCampaignFactory> */
    use HasFactory, HasUlid, Publishable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'raised_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function progressPercentage(): float
    {
        if ($this->goal_amount <= 0) {
            return 100.0;
        }

        return round(min(100.0, ($this->raised_amount / $this->goal_amount) * 100), 1);
    }

    public function isGoalReached(): bool
    {
        return $this->raised_amount >= $this->goal_amount;
    }

    public function daysLeft(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        if ($this->ends_at->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($this->ends_at, false));
    }
}
