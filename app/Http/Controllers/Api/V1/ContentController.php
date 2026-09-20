<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GalleryResource;
use App\Http\Resources\Api\V1\NewsResource;
use App\Models\Announcement;
use App\Models\GalleryAlbum;
use App\Models\News;
use App\Support\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public content API — news, announcements, gallery.
 *
 * No authentication required. Published content only.
 *
 * @see docs/10-api.md section 3
 */
class ContentController extends Controller
{
    /**
     * Published news articles, most recent first.
     */
    public function news(Request $request): JsonResponse
    {
        $news = News::query()
            ->published()
            ->latest('published_at')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'news' => NewsResource::collection($news)->response()->getData(true),
        ]);
    }

    /**
     * A single published news article.
     */
    public function newsShow(News $news): JsonResponse
    {
        abort_unless($news->status === ContentStatus::Published, 404);

        return response()->json([
            'article' => NewsResource::make($news)->resolve(),
        ]);
    }

    /**
     * Live announcements visible to the public.
     */
    public function announcements(Request $request): JsonResponse
    {
        $announcements = Announcement::query()
            ->live()
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'announcements' => Paginated::from($announcements, fn (Announcement $a): array => [
                'id' => $a->id,
                'kind' => $a->kind->value,
                'title' => $a->title,
                'body' => $a->body,
                'level' => $a->level->value,
                'audience' => $a->audience->value,
                'is_pinned' => $a->is_pinned,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'ends_at' => $a->ends_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Published gallery albums.
     */
    public function gallery(Request $request): JsonResponse
    {
        $albums = GalleryAlbum::query()
            ->where('status', ContentStatus::Published)
            ->withCount('images')
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'albums' => GalleryResource::collection($albums)->response()->getData(true),
        ]);
    }

    /**
     * A published gallery album with its images.
     */
    public function galleryShow(GalleryAlbum $album): JsonResponse
    {
        abort_unless($album->status === ContentStatus::Published, 404);

        $album->load('images');

        return response()->json([
            'album' => GalleryResource::make($album)->resolve(),
        ]);
    }
}
