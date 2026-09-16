<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\SponsorTier;
use Carbon\CarbonImmutable;
use Database\Factories\SponsorshipPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sponsorship tier.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $name_bn
 * @property SponsorTier $tier
 * @property string|null $amount
 * @property string $currency
 * @property string|null $benefits
 * @property string|null $benefits_bn
 * @property int|null $max_slots
 * @property int $display_order
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'slug', 'name', 'name_bn', 'tier', 'amount', 'currency', 'benefits', 'benefits_bn', 'max_slots',
    'display_order', 'is_active',
])]
class SponsorshipPackage extends Model
{
    /** @use HasFactory<SponsorshipPackageFactory> */
    use HasFactory, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['name', 'benefits'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => SponsorTier::class,
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Sponsor, $this>
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }
}
