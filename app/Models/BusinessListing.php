<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Concerns\Publishable;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BusinessListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An alumni-owned business or professional practice.
 *
 * @property int $id
 * @property string $ulid
 * @property int $member_id
 * @property string $name
 * @property string $category
 * @property string|null $industry
 * @property string|null $tagline
 * @property string|null $description
 * @property string|null $address
 * @property string|null $city
 * @property string|null $district
 * @property string $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $logo_path
 * @property string|null $alumni_discount
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'member_id', 'name', 'category', 'industry', 'tagline',
    'description', 'address', 'city', 'district', 'country',
    'phone', 'email', 'website', 'logo_path', 'alumni_discount',
    'status', 'published_at',
])]
class BusinessListing extends Model
{
    /** @use HasFactory<BusinessListingFactory> */
    use HasFactory, HasUlid, Publishable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
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
