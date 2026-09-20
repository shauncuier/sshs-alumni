<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsRequest;
use App\Models\News;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Support\Paginated;
use App\Support\SlugFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * News articles.
 *
 * WRITING AND PUBLISHING ARE DIFFERENT PERMISSIONS. `content.manage` writes;
 * `content.publish` makes it public. A batch coordinator can draft an item
 * about their own cohort without being able to put it on the front page, and
 * that separation is the whole reason the two permissions exist rather than
 * one.
 *
 * The slug is assigned once, at creation, and never changes with the title.
 * A published URL that a member has shared or a search engine has indexed is
 * a promise; renaming an article should not break it.
 *
 * @see docs/05-modules.md section 12
 */
class NewsController extends Controller
{
    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $news = News::query()
            ->with('author')
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $needle = '%'.$filters['q'].'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', $needle)
                    ->orWhere('excerpt', 'like', $needle));
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/news/index', [
            'news' => Paginated::from($news, fn (News $item): array => $this->row($item)),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => [
                'statuses' => ContentStatus::options(),
                'categories' => $this->categories(),
            ],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
                'publish' => $request->user()?->can('content.publish') ?? false,
            ],
        ]);
    }

    public function store(NewsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var User $actor */
        $actor = $request->user();

        $news = News::query()->create([
            ...$validated,
            'slug' => SlugFactory::unique(News::class, $validated['title'], 'news'),
            'author_id' => $actor->id,
            'status' => ContentStatus::Draft,
        ]);

        $this->attachCover($request, $news);

        return back()->with('success', __('admin.content.saved_draft'));
    }

    public function update(NewsRequest $request, News $news): RedirectResponse
    {
        // Slug deliberately absent: see the class docblock.
        $news->update($request->validated());

        $this->attachCover($request, $news);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Publish or take down.
     *
     * `Publishable::publish()` keeps an existing `published_at`, so correcting
     * a typo in a two-year-old article does not republish it to the top of the
     * list.
     */
    public function publish(Request $request, News $news): RedirectResponse
    {
        $publish = $request->boolean('publish', true);

        $publish ? $news->publish() : $news->unpublish();

        return back()->with('success', $publish
            ? __('admin.content.published')
            : __('admin.content.unpublished'));
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    private function attachCover(Request $request, News $news): void
    {
        $cover = $request->file('cover');

        if (! $cover instanceof UploadedFile) {
            return;
        }

        $request->validate([
            'cover' => $this->media->validationRules(MediaCollection::Cover),
        ]);

        $media = $this->media->store($cover, MediaCollection::Cover, $news);

        $news->update(['cover_path' => $media->path]);
    }

    /**
     * The categories already in use, so the filter can never offer one that
     * returns nothing and the editor is nudged towards reusing a name rather
     * than inventing a synonym.
     *
     * @return array<int, string>
     */
    private function categories(): array
    {
        /** @var array<int, string> $categories */
        $categories = News::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return $categories;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(News $item): array
    {
        return [
            'id' => $item->id,
            'slug' => $item->slug,
            'title' => $item->title,
            'excerpt' => $item->excerpt,
            'body' => $item->body,
            'category' => $item->category,
            'is_featured' => $item->is_featured,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'published_at' => $item->published_at?->toIso8601String(),
            'views_count' => $item->views_count,
            'author' => $item->author?->name,
            'cover_url' => $item->cover_path === null ? null : asset('storage/'.$item->cover_path),
            'meta_title' => $item->meta_title,
            'meta_description' => $item->meta_description,
            'url' => route('news.show', $item->slug, absolute: false),
            'summary' => Str::limit(strip_tags($item->body), 160),
        ];
    }
}
