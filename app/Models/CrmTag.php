<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CrmTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * A tag applying polymorphically to members and contacts alike.
 *
 * @property int $id
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $color
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name', 'name_bn', 'color', 'description',
])]
class CrmTag extends Model
{
    /** @use HasFactory<CrmTagFactory> */
    use HasFactory;

    /**
     * @return MorphToMany<Member, $this>
     */
    public function members(): MorphToMany
    {
        return $this->morphedByMany(Member::class, 'taggable', 'crm_taggables', null, 'crm_tag_id')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<CrmContact, $this>
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(CrmContact::class, 'taggable', 'crm_taggables', null, 'crm_tag_id')
            ->withTimestamps();
    }
}
