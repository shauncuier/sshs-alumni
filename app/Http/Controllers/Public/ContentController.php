<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\StoryStatus;
use App\Http\Controllers\Controller;
use App\Models\AlumniStory;
use App\Models\Announcement;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\News;
use App\Models\Page;
use App\Models\SchoolMilestone;
use App\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything the public reads: news, announcements, the gallery, alumni
 * stories, the school's timeline and the standing pages.
 *
 * ONE CONTROLLER, because every one of these methods is the same three lines —
 * take the published rows, map them, render. Seven controllers of three lines
 * would be six files of ceremony around one rule, and the rule is the thing
 * worth keeping in one place:
 *
 *   A DRAFT DOES NOT EXIST. Every finder below goes through the model's
 *   `published()` scope and 404s otherwise. Not 403 — "forbidden" tells a
 *   stranger that an article they cannot read is being written, and the
 *   difference matters when the draft is an announcement about somebody's
 *   death or a committee decision not yet taken.
 *
 * @see app/Concerns/Publishable.php
 */
class ContentController extends Controller
{
    // ── News ────────────────────────────────────────────────────────────

    public function news(Request $request): Response
    {
        $category = (string) $request->query('category', '');

        $news = News::query()
            ->published()
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('public/news', [
            'news' => Paginated::from($news, fn (News $item): array => $this->newsCard($item)),
            'filters' => ['category' => $category === '' ? null : $category],
            'categories' => $this->newsCategories(),
            'featured' => Inertia::defer(fn (): ?array => $this->featuredNews()),
        ]);
    }

    public function newsShow(News $news): Response
    {
        abort_unless($news->isPublished(), 404);

        // A read counter, not an analytics system. `increment` rather than
        // save() so two readers at once do not overwrite each other's count,
        // and it deliberately does not touch `updated_at` — reading an article
        // is not editing it.
        DB::table('news')->where('id', $news->id)->increment('views_count');

        return Inertia::render('public/news-show', [
            'article' => [
                ...$this->newsCard($news),
                'body' => $news->body,
                'author' => $news->author?->name,
                'meta_title' => $news->meta_title,
                'meta_description' => $news->meta_description
                    ?? Str::limit(strip_tags($news->body), 160),
            ],
            'related' => Inertia::defer(fn (): array => $this->relatedNews($news)),
        ]);
    }

    // ── Announcements ───────────────────────────────────────────────────

    /**
     * Public announcements, live ones first.
     *
     * A signed-in member sees their own batch's notices here too — the scope
     * takes the reader, so the same page serves a stranger and a member
     * without a second route.
     */
    public function announcements(Request $request): Response
    {
        $reader = $request->user()?->member;

        $announcements = Announcement::query()
            ->live()
            ->forAudience($reader)
            ->with('batch')
            ->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('public/announcements', [
            'announcements' => Paginated::from(
                $announcements,
                fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'kind' => $announcement->kind->value,
                    'kind_label' => $announcement->kind->label(),
                    'level' => $announcement->level->value,
                    'level_label' => $announcement->level->label(),
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'batch' => $announcement->batch?->name,
                    'starts_at' => $announcement->starts_at?->toIso8601String(),
                    'ends_at' => $announcement->ends_at?->toIso8601String(),
                    'is_pinned' => $announcement->is_pinned,
                    'attachment_url' => $announcement->attachment_path === null
                        ? null
                        : asset('storage/'.$announcement->attachment_path),
                ],
            ),
        ]);
    }

    // ── Gallery ─────────────────────────────────────────────────────────

    public function gallery(): Response
    {
        $albums = GalleryAlbum::query()
            ->published()
            ->with(['event', 'batch'])
            ->orderBy('display_order')
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('public/gallery', [
            'albums' => Paginated::from($albums, fn (GalleryAlbum $album): array => [
                'slug' => $album->slug,
                'title' => $album->title,
                'description' => $album->description,
                'images_count' => $album->images_count,
                'event' => $album->event?->title,
                'batch' => $album->batch?->name,
                'published_at' => $album->published_at?->toIso8601String(),
                'cover_url' => $this->albumCover($album),
                'url' => route('gallery.show', $album->slug, absolute: false),
            ]),
        ]);
    }

    public function galleryShow(GalleryAlbum $album): Response
    {
        abort_unless($album->isPublished(), 404);

        $album->load(['images.media', 'event', 'batch']);

        return Inertia::render('public/gallery-show', [
            'album' => [
                'slug' => $album->slug,
                'title' => $album->title,
                'description' => $album->description,
                'event' => $album->event?->title,
                'batch' => $album->batch?->name,
                'published_at' => $album->published_at?->toIso8601String(),
                'images_count' => $album->images_count,
            ],
            'images' => $album->images
                ->sortBy('display_order')
                ->map(fn (GalleryImage $image): array => [
                    'id' => $image->id,
                    'caption' => $image->caption,
                    'url' => asset('storage/'.$image->media->path),
                    'thumb_url' => $image->media->thumb_path === null
                        ? asset('storage/'.$image->media->path)
                        : asset('storage/'.$image->media->thumb_path),
                    'width' => $image->media->width,
                    'height' => $image->media->height,
                ])
                ->values()
                ->all(),
        ]);
    }

    // ── Alumni stories ──────────────────────────────────────────────────

    public function stories(): Response
    {
        $stories = AlumniStory::query()
            ->published()
            ->with('batch')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(9);

        return Inertia::render('public/stories', [
            'stories' => Paginated::from(
                $stories,
                fn (AlumniStory $story): array => $this->storyCard($story),
            ),
        ]);
    }

    public function storyShow(AlumniStory $story): Response
    {
        abort_unless($story->status === StoryStatus::Published, 404);

        $story->load('batch');

        return Inertia::render('public/story-show', [
            'story' => [
                ...$this->storyCard($story),
                'body' => $story->body,
                'meta_description' => $story->meta_description
                    ?? Str::limit(strip_tags($story->body), 160),
            ],
        ]);
    }

    // ── About, the school, and standing pages ───────────────────────────

    public function about(): Response
    {
        return Inertia::render('public/about', [
            'organisation' => [
                'name_bn' => setting('organization.name_bn'),
                'name_en' => setting('organization.name_en'),
                'established' => setting('organization.established'),
            ],
            'school' => [
                'name_bn' => setting('school.name_bn'),
                'name_en' => setting('school.name_en'),
                'established' => setting('school.established'),
                'eiin' => setting('school.eiin'),
                'address' => setting('school.address'),
            ],
            'stats' => Inertia::defer(fn (): array => [
                'milestones' => SchoolMilestone::query()->count(),
                'albums' => GalleryAlbum::query()->published()->count(),
            ]),
            'faqs' => Inertia::defer(fn (): array => $this->faqs()),
        ]);
    }

    /**
     * The school's fifty years.
     *
     * Seeded with two milestones and left open — 1976 when the school opened,
     * 2015 when the association was founded. The page says outright that the
     * rest is still being collected, because a timeline with two entries and
     * no explanation looks broken rather than unfinished.
     */
    public function school(): Response
    {
        $milestones = SchoolMilestone::query()
            ->orderBy('year')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('public/school', [
            'school' => [
                'name_bn' => setting('school.name_bn'),
                'name_en' => setting('school.name_en'),
                'established' => setting('school.established'),
                'eiin' => setting('school.eiin'),
                'board' => setting('school.board'),
                'address' => setting('school.address'),
                'head_teacher' => setting('school.head_teacher'),
                'motto_en' => setting('school.motto_en'),
            ],
            'milestones' => $milestones
                ->map(fn (SchoolMilestone $milestone): array => [
                    'id' => $milestone->id,
                    'year' => $milestone->year,
                    'date_label' => $milestone->date_label,
                    'title' => $milestone->title,
                    'description' => $milestone->description,
                    'is_highlighted' => $milestone->is_highlighted,
                    'image_url' => $milestone->image_path === null
                        ? null
                        : asset('storage/'.$milestone->image_path),
                ])
                ->all(),
        ]);
    }

    public function page(Page $page): Response
    {
        abort_unless($page->isPublished(), 404);

        return Inertia::render('public/page', [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'body' => $page->body,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
        ]);
    }

    // ── Shared shapes ───────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function newsCard(News $item): array
    {
        return [
            'slug' => $item->slug,
            'title' => $item->title,
            'excerpt' => $item->excerpt ?? Str::limit(strip_tags($item->body), 160),
            'category' => $item->category,
            'is_featured' => $item->is_featured,
            'published_at' => $item->published_at?->toIso8601String(),
            'views_count' => $item->views_count,
            'cover_url' => $item->cover_path === null ? null : asset('storage/'.$item->cover_path),
            'url' => route('news.show', $item->slug, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storyCard(AlumniStory $story): array
    {
        return [
            'slug' => $story->slug,
            'title' => $story->title,
            'author_name' => $story->author_name,
            'batch' => $story->batch?->name,
            'career_summary' => $story->career_summary,
            'excerpt' => Str::limit(strip_tags($story->body), 200),
            'is_featured' => $story->is_featured,
            'published_at' => $story->published_at?->toIso8601String(),
            'photo_url' => $story->photo_path === null ? null : asset('storage/'.$story->photo_path),
            'url' => route('stories.show', $story->slug, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function featuredNews(): ?array
    {
        $featured = News::query()
            ->published()
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->first();

        return $featured === null ? null : $this->newsCard($featured);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function relatedNews(News $news): array
    {
        return News::query()
            ->published()
            ->whereKeyNot($news->id)
            ->when(
                $news->category !== null,
                fn (Builder $query) => $query->where('category', $news->category),
            )
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn (News $item): array => $this->newsCard($item))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function newsCategories(): array
    {
        /** @var array<int, string> $categories */
        $categories = News::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return $categories;
    }

    /**
     * The album's own cover, or its first photograph.
     *
     * An album with photographs in it should never render as a grey box
     * because nobody chose a cover.
     */
    private function albumCover(GalleryAlbum $album): ?string
    {
        if ($album->cover_path !== null) {
            return asset('storage/'.$album->cover_path);
        }

        $first = $album->images()->with('media')->orderBy('display_order')->first();

        if ($first === null) {
            return null;
        }

        return asset('storage/'.($first->media->thumb_path ?? $first->media->path));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function faqs(): array
    {
        return Faq::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('display_order')
            ->get()
            ->map(fn (Faq $faq): array => [
                'id' => $faq->id,
                'group' => $faq->group->value,
                'group_label' => $faq->group->label(),
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])
            ->all();
    }
}
