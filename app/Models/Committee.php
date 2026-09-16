<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\CommitteeType;
use Carbon\CarbonImmutable;
use Database\Factories\CommitteeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A committee with a term window.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $name_bn
 * @property CommitteeType $type
 * @property string|null $description
 * @property string|null $description_bn
 * @property CarbonImmutable|null $term_start
 * @property CarbonImmutable|null $term_end
 * @property string $status
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'name', 'name_bn', 'type', 'description', 'description_bn', 'term_start', 'term_end',
    'status', 'display_order',
])]
class Committee extends Model
{
    /** @use HasFactory<CommitteeFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommitteeType::class,
            'term_start' => 'date',
            'term_end' => 'date',
        ];
    }

    /**
     * @return HasMany<CommitteeMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }
}
