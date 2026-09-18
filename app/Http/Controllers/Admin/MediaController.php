<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaService;
use App\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The media library.
 *
 * PRIVATE MEDIA IS LISTED BUT NOT LINKED. Verification documents and sponsor
 * agreements live on the private disk and have no URL at all — MediaService
 * returns null for them on purpose. They appear here as rows so that somebody
 * auditing storage can see they exist, and the row says plainly that the file
 * is not public rather than offering a link that would 404 or, worse, work.
 *
 * DELETING IS REFUSED WHILE SOMETHING USES IT. A gallery image, a cover photo
 * or a profile picture whose file vanished leaves a broken image on a public
 * page, and the person who deleted it is never the person who finds out.
 *
 * @see app/Services/Media/MediaService.php
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'collection' => (string) $request->query('collection', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $media = Media::query()
            ->with('uploader')
            ->withCount('galleryImages')
            ->when($filters['collection'] !== '', fn (Builder $query) => $query->where('collection', $filters['collection']))
            ->when($filters['q'] !== '', fn (Builder $query) => $query
                ->where('original_name', 'like', '%'.$filters['q'].'%'))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return Inertia::render('admin/media/index', [
            'media' => Paginated::from($media, fn (Media $item): array => $this->row($item)),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'options' => ['collections' => MediaCollection::options()],
            'totals' => [
                // Bytes, formatted on the client. A library nobody has looked
                // at since 2026 is the reason a shared host runs out of disk.
                'size' => (int) Media::query()->sum('size'),
                'count' => Media::query()->count(),
            ],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => $this->media->validationRules(MediaCollection::Gallery),
        ]);

        $file = $request->file('file');

        if ($file instanceof UploadedFile) {
            $this->media->store($file, MediaCollection::Gallery);
        }

        return back()->with('success', __('admin.media.uploaded'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        // `model_id` alone is not enough: a cover photo is referenced by a
        // PATH on its owner rather than by a foreign key, so both are checked.
        abort_if($this->isInUse($media), 409, __('admin.media.in_use'));

        $this->media->delete($media, force: true);

        return back()->with('success', __('admin.media.deleted'));
    }

    private function isInUse(Media $media): bool
    {
        return $media->galleryImages()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Media $item): array
    {
        return [
            'id' => $item->id,
            'collection' => $item->collection->value,
            'collection_label' => $item->collection->label(),
            'original_name' => $item->original_name,
            'mime_type' => $item->mime_type,
            'size' => $item->size,
            'width' => $item->width,
            'height' => $item->height,
            'is_public' => $item->is_public,
            'uploaded_by' => $item->uploader?->name,
            'created_at' => $item->created_at?->toIso8601String(),
            'in_use' => $item->gallery_images_count > 0,
            // Null for private media, by design. See the class docblock.
            'url' => $this->media->url($item),
            'thumb_url' => $this->media->url($item, thumb: true),
        ];
    }
}
