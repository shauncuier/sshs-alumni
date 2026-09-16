<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\CrmContactType;
use App\Enums\PipelineStage;
use Carbon\CarbonImmutable;
use Database\Factories\CrmContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Anyone the association deals with who is not (yet) an alumni record.
 *
 * When a contact turns out to be an alumnus, `member_id` links them — the
 * person is never duplicated.
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $member_id
 * @property CrmContactType $type
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $organization_name
 * @property string|null $designation
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $address
 * @property string|null $city
 * @property string|null $district
 * @property string|null $country
 * @property string|null $source
 * @property string|null $relationship_type
 * @property PipelineStage $pipeline_status
 * @property int|null $owner_id
 * @property CarbonImmutable|null $last_activity_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'member_id', 'type', 'name', 'name_bn', 'organization_name', 'designation', 'email', 'phone',
    'whatsapp', 'address', 'city', 'district', 'country', 'source', 'relationship_type',
    'pipeline_status', 'owner_id', 'notes',
])]
class CrmContact extends Model
{
    /** @use HasFactory<CrmContactFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CrmContactType::class,
            'pipeline_status' => PipelineStage::class,
            'last_activity_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return MorphMany<CrmActivity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject');
    }

    /**
     * @return MorphMany<CrmTask, $this>
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'subject');
    }

    /**
     * @return MorphToMany<CrmTag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(CrmTag::class, 'taggable', 'crm_taggables', null, 'crm_tag_id')
            ->withTimestamps();
    }
}
