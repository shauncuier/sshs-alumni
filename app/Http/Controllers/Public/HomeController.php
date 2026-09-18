<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicEventResource;
use App\Models\AlumniStory;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\Member;
use App\Models\News;
use App\Models\SchoolMilestone;
use App\Services\Events\EventRegistrar;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The front door.
 *
 * WHAT IT PROMISES. Everything on this page is real: the counts are queried,
 * the events are published events, the photographs are uploaded photographs.
 * Sections with nothing in them do not render at all — an alumni site whose
 * home page shows "Latest news" above an empty box tells a first-time visitor
 * that nobody is looking after it, which on a volunteer-run site is the one
 * impression worth avoiding.
 *
 * THE FOUR COUNTS ARE THE CHEAP ONES. Approved members, batches with members
 * in them, published events, and the school's age. They are the first query
 * the page makes and they load with it; everything below the fold is deferred,
 * so the hero paints before the photographs are counted.
 *
 * The Jubilee strip obeys the same date rule as everywhere else: the countdown
 * renders only once the committee has announced a date, and until then the
 * page says so rather than inventing one.
 *
 * @see docs/17-golden-jubilee.md
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('public/home', [
            'stats' => $this->stats(),
            'jubilee' => $this->jubilee(),

            // Below the fold. Deferred so the hero is not waiting on a count
            // of photographs.
            'events' => Inertia::defer(fn (): array => $this->upcomingEvents()),
            'news' => Inertia::defer(fn (): array => $this->latestNews()),
            'announcements' => Inertia::defer(fn (): array => $this->announcements()),
            'photos' => Inertia::defer(fn (): array => $this->recentPhotos()),
            'stories' => Inertia::defer(fn (): array => $this->featuredStories()),
            'timeline' => Inertia::defer(fn (): array => $this->timeline()),
        ]);
    }

    /**
     * @return array{members: int, batches: int, events: int, years: int}
     */
    private function stats(): array
    {
        $founded = (int) setting('school.established', 1976);

        return [
            'members' => Member::query()->where('status', MemberStatus::Approved)->count(),
            // Batches WITH members. Every SSC year from 1981 exists as a row
            // whether or not anybody from it has joined, and counting those
            // would claim a reach the association does not have yet.
            'batches' => Batch::query()->where('members_count', '>', 0)->count(),
            'events' => Event::query()->published()->count(),
            'years' => max(0, (int) now()->year - $founded),
        ];
    }

    /**
     * The flagship event, for the Jubilee strip.
     *
     * @return array<string, mixed>|null
     */
    private function jubilee(): ?array
    {
        $event = Event::query()
            ->published()
            ->where('is_flagship', true)
            ->with('ticketTypes')
            ->first();

        if ($event === null) {
            return null;
        }

        return [
            'event' => PublicEventResource::make($event)->resolve(),
            'title_bn' => setting('jubilee.title_bn'),
            'theme_bn' => setting('jubilee.theme_bn'),
            'from_year' => setting('jubilee.from_year'),
            'to_year' => setting('jubilee.to_year'),
            'show_countdown' => (bool) setting('jubilee.show_countdown', true),
            'seats_left' => $this->registrar->seatsLeft($event),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingEvents(): array
    {
        $events = Event::query()
            ->published()
            ->where('is_flagship', false)
            ->where(fn ($query) => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '>=', now()->startOfDay()))
            ->orderByRaw('case when starts_at is null then 1 else 0 end')
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return PublicEventResource::collection($events)->resolve();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function latestNews(): array
    {
        return News::query()
            ->published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn (News $item): array => [
                'slug' => $item->slug,
                'title' => $item->title,
                'excerpt' => $item->excerpt ?? Str::limit(strip_tags($item->body), 160),
                'published_at' => $item->published_at?->toIso8601String(),
                'cover_url' => $item->cover_path === null ? null : asset('storage/'.$item->cover_path),
                'url' => route('news.show', $item->slug, absolute: false),
            ])
            ->all();
    }

    /**
     * Public announcements that are live right now.
     *
     * A visitor is not a member, so `forAudience(null)` narrows this to the
     * `public` audience — members-only notices do not leak onto the front
     * page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function announcements(): array
    {
        return Announcement::query()
            ->live()
            ->forAudience(null)
            ->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'kind' => $announcement->kind->value,
                'level' => $announcement->level->value,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'starts_at' => $announcement->starts_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPhotos(): array
    {
        return GalleryImage::query()
            ->whereHas('album', fn ($query) => $query->published())
            ->with(['media', 'album'])
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (GalleryImage $image): array => [
                'id' => $image->id,
                'caption' => $image->caption,
                'album' => $image->album->title,
                'url' => route('gallery.show', $image->album->slug, absolute: false),
                'thumb_url' => $image->media->thumb_path === null
                    ? asset('storage/'.$image->media->path)
                    : asset('storage/'.$image->media->thumb_path),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function featuredStories(): array
    {
        return AlumniStory::query()
            ->published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(2)
            ->get()
            ->map(fn (AlumniStory $story): array => [
                'slug' => $story->slug,
                'title' => $story->title,
                'author_name' => $story->author_name,
                'career_summary' => $story->career_summary,
                'excerpt' => Str::limit(strip_tags($story->body), 180),
                'photo_url' => $story->photo_path === null ? null : asset('storage/'.$story->photo_path),
                'url' => route('stories.show', $story->slug, absolute: false),
            ])
            ->all();
    }

    /**
     * The highlighted milestones, for the strip that links to the full
     * timeline.
     *
     * @return array<int, array<string, mixed>>
     */
    private function timeline(): array
    {
        return SchoolMilestone::query()
            ->orderByDesc('is_highlighted')
            ->orderBy('year')
            ->limit(4)
            ->get()
            ->sortBy('year')
            ->map(fn (SchoolMilestone $milestone): array => [
                'year' => $milestone->year,
                'title' => $milestone->title,
                'description' => $milestone->description,
            ])
            ->values()
            ->all();
    }
}
