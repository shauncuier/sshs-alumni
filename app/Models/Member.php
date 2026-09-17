<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasUlid;
use App\Concerns\Translatable;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\RelationType;
use Carbon\CarbonImmutable;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The canonical alumni record.
 *
 * A User is a login identity; a Member is an alumni record. They link 1:1 but
 * either can exist alone — the association enters alumni from paper registers
 * who will never log in, and office staff have logins with no alumni record.
 *
 * @see docs/02-database-schema.md section 4
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $user_id
 * @property string|null $membership_no
 * @property string $full_name
 * @property string|null $full_name_bn
 * @property string|null $photo_path
 * @property CarbonImmutable|null $date_of_birth
 * @property Gender|null $gender
 * @property BloodGroup|null $blood_group
 * @property RelationType $relation_type
 * @property int|null $batch_id
 * @property int|null $ssc_year
 * @property string|null $student_id
 * @property int|null $admission_year
 * @property string|null $group_stream
 * @property string|null $section
 * @property string|null $house
 * @property string|null $higher_education
 * @property string|null $occupation
 * @property string|null $organization
 * @property string|null $job_title
 * @property string|null $industry
 * @property string|null $business_info
 * @property string|null $country
 * @property string|null $division
 * @property string|null $district
 * @property string|null $city
 * @property string|null $address
 * @property string|null $mobile
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $bio
 * @property string|null $bio_bn
 * @property array<array-key, mixed>|null $skills
 * @property array<array-key, mixed>|null $interests
 * @property MemberStatus $status
 * @property CarbonImmutable|null $verified_at
 * @property int|null $verified_by
 * @property CarbonImmutable|null $registered_at
 * @property int $profile_completion
 * @property bool $is_featured
 * @property string|null $search_blob
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'user_id', 'full_name', 'full_name_bn', 'photo_path', 'date_of_birth', 'gender', 'blood_group',
    'relation_type', 'batch_id', 'ssc_year', 'student_id', 'admission_year', 'group_stream',
    'section', 'house', 'higher_education', 'occupation', 'organization', 'job_title', 'industry',
    'business_info', 'country', 'division', 'district', 'city', 'address', 'mobile', 'whatsapp',
    'email', 'emergency_contact_name', 'emergency_contact_phone', 'bio', 'bio_bn', 'skills',
    'interests', 'registered_at',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use Auditable, HasFactory, HasUlid, SoftDeletes, Translatable;

    /**
     * Bio is authored in both languages by the member themselves.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['bio'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MemberStatus::class,
            'relation_type' => RelationType::class,
            'gender' => Gender::class,
            'blood_group' => BloodGroup::class,
            'date_of_birth' => 'date',
            'verified_at' => 'datetime',
            'registered_at' => 'datetime',
            'skills' => 'array',
            'interests' => 'array',
            'is_featured' => 'boolean',
            'profile_completion' => 'integer',
            'ssc_year' => 'integer',
            'admission_year' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasOne<MemberPrivacy, $this>
     */
    public function privacy(): HasOne
    {
        return $this->hasOne(MemberPrivacy::class);
    }

    /**
     * @return HasOne<Volunteer, $this>
     */
    public function volunteer(): HasOne
    {
        return $this->hasOne(Volunteer::class);
    }

    /**
     * @return HasOne<CrmContact, $this>
     */
    public function crmContact(): HasOne
    {
        return $this->hasOne(CrmContact::class);
    }

    /**
     * @return HasMany<MemberVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(MemberVerification::class);
    }

    /**
     * @return HasMany<MemberLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(MemberLink::class);
    }

    /**
     * @return HasMany<MemberEducation, $this>
     */
    public function education(): HasMany
    {
        return $this->hasMany(MemberEducation::class);
    }

    /**
     * @return HasMany<MemberEmployment, $this>
     */
    public function employment(): HasMany
    {
        return $this->hasMany(MemberEmployment::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<MembershipFee, $this>
     */
    public function membershipFees(): HasMany
    {
        return $this->hasMany(MembershipFee::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_member_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_member_id');
    }

    /**
     * @return HasMany<AlumniStory, $this>
     */
    public function stories(): HasMany
    {
        return $this->hasMany(AlumniStory::class);
    }

    /**
     * @return HasMany<CommitteeMember, $this>
     */
    public function committeeMemberships(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    /**
     * @return MorphMany<CrmActivity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject');
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * @return MorphToMany<CrmTag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(CrmTag::class, 'taggable', 'crm_taggables', null, 'crm_tag_id')
            ->withTimestamps();
    }

    /**
     * Batches this member coordinates. Grants the Batch Coordinator role its
     * scoped reach — enforced in policies, not by permission name alone.
     *
     * @return BelongsToMany<Batch, $this>
     */
    public function coordinatedBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_coordinators')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * Only members who may appear in the directory at all.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDirectoryVisible(Builder $query): void
    {
        $query->where('status', MemberStatus::Approved)
            ->whereHas('privacy', fn (Builder $q) => $q->where('show_profile', true));
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', MemberStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->status === MemberStatus::Approved;
    }
}
