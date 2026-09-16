<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasUlid;
use App\Concerns\Translatable;
use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A general-purpose event. The Golden Jubilee is one row here with
 * `is_flagship = true` — not a separate subsystem.
 *
 * THE DATE RULE: `starts_at` is nullable and `date_status` defaults to `tba`.
 * No Golden Jubilee date literal exists anywhere in this codebase.
 *
 * @see docs/17-golden-jubilee.md
 *
 * @property int $id
 * @property string $ulid
 * @property string $slug
 * @property EventType $type
 * @property string $title
 * @property string|null $title_bn
 * @property string|null $summary
 * @property string|null $summary_bn
 * @property string|null $description
 * @property string|null $description_bn
 * @property string|null $cover_path
 * @property EventDateStatus $date_status
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string|null $venue
 * @property string|null $venue_bn
 * @property string|null $address
 * @property string|null $map_url
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $registration_required
 * @property CarbonImmutable|null $registration_opens_at
 * @property CarbonImmutable|null $registration_closes_at
 * @property int|null $capacity
 * @property string|null $registration_fee
 * @property string $currency
 * @property string|null $organizer_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property EventStatus $status
 * @property bool $is_flagship
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image_path
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'type', 'title', 'title_bn', 'summary', 'summary_bn', 'description', 'description_bn',
    'cover_path', 'date_status', 'starts_at', 'ends_at', 'venue', 'venue_bn', 'address', 'map_url',
    'latitude', 'longitude', 'registration_required', 'registration_opens_at',
    'registration_closes_at', 'capacity', 'registration_fee', 'currency', 'organizer_name',
    'contact_phone', 'contact_email', 'status', 'meta_title', 'meta_description', 'og_image_path',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use Auditable, HasFactory, HasUlid, SoftDeletes, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['title', 'summary', 'description', 'venue'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'date_status' => EventDateStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'published_at' => 'datetime',
            'registration_required' => 'boolean',
            'is_flagship' => 'boolean',
            'registration_fee' => 'decimal:2',
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<EventTicketType, $this>
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<EventCheckin, $this>
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(EventCheckin::class);
    }

    /**
     * @return HasMany<Sponsor, $this>
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return HasMany<GalleryAlbum, $this>
     */
    public function galleryAlbums(): HasMany
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    /**
     * @return HasMany<VolunteerAssignment, $this>
     */
    public function volunteerAssignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * True while the committee has not published a date. Public surfaces then
     * render "তারিখ শীঘ্রই ঘোষণা করা হবে" and no countdown is rendered at all.
     */
    public function dateIsTba(): bool
    {
        return $this->date_status === EventDateStatus::Tba || $this->starts_at === null;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotIn('status', [EventStatus::Draft, EventStatus::Cancelled]);
    }
}
