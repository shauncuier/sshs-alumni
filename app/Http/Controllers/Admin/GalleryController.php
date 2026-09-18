<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Services\Media\MediaService;
use App\Support\SlugFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Photo albums.
 *
 * An album may be tied to an event or a batch, and usually is: "Reunion 2019"
 * means more next to the event it belongs to, and a batch album is how a
 * cohort finds its own photographs fifty years later.
 *
 * `images_count` is a counter cache kept by recounting — see the community
 * phase for why incrementing loses. Deleting an image deletes the underlying
 * media file too, because an orphaned upload is storage nobody will ever
 * account for.
 *
 * @see docs/05-modules.md section 12
 */
class GalleryController extends Controller
{
    /**
     * How many photographs may be uploaded in one go.
     *
     * Twenty because PHP's own `max_file_uploads` defaults to twenty, and a
     * limit the server silently enforces is worse than one the form states.
     */
    private const MAX_UPLOAD = 20;

    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $albums = GalleryAlbum::query()
            ->with(['event', 'batch'])
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('admin/gallery/index', [
            'albums' => $albums->map(fn (GalleryAlbum $album): array => $this->row($album))->all(),
            'options' => [
                'statuses' => ContentStatus::options(),
                'max_upload' => self::MAX_UPLOAD,
                'events' => Event::query()
                    ->orderByDesc('id')
                    ->get(['id', 'title'])
                    ->map(fn (Event $event): array => [
                        'value' => (string) $event->id,
                        'label' => $event->title,
                    ])
                    ->all(),
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name'])
                    ->map(fn (Batch $batch): array => [
                        'value' => (string) $batch->id,
                        'label' => $batch->name,
                    ])
                    ->all(),
            ],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
                'publish' => $request->user()?->can('content.publish') ?? false,
            ],
        ]);
    }

    public function show(GalleryAlbum $album): Response
    {
        $album->load(['images.media', 'event', 'batch']);

        return Inertia::render('admin/gallery/show', [
            'album' => $this->row($album),
            'images' => $album->images
                ->sortBy('display_order')
                ->map(fn (GalleryImage $image): array => [
                    'id' => $image->id,
                    'caption' => $image->caption,
                    'display_order' => $image->display_order,
                    'url' => asset('storage/'.$image->media->path),
                    'thumb_url' => $image->media->thumb_path === null
                        ? asset('storage/'.$image->media->path)
                        : asset('storage/'.$image->media->thumb_path),
                ])
                ->values()
                ->all(),
            'options' => ['max_upload' => self::MAX_UPLOAD],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        GalleryAlbum::query()->create([
            ...$validated,
            'slug' => SlugFactory::unique(GalleryAlbum::class, $validated['title'], 'album'),
            'status' => ContentStatus::Draft,
        ]);

        return back()->with('success', __('admin.content.saved_draft'));
    }

    public function update(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $album->update($this->validated($request));

        return back()->with('success', __('common.states.saved'));
    }

    public function publish(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $publish = $request->boolean('publish', true);

        $publish ? $album->publish() : $album->unpublish();

        return back()->with('success', $publish
            ? __('admin.content.published')
            : __('admin.content.unpublished'));
    }

    public function destroy(GalleryAlbum $album): RedirectResponse
    {
        $album->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    /**
     * Upload photographs into an album.
     *
     * Every file goes through MediaService, which re-encodes it — that strips
     * the GPS coordinates a phone writes into a photograph, which nobody
     * uploading a reunion picture expects to be publishing.
     */
    public function upload(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:'.self::MAX_UPLOAD],
            'photos.*' => $this->media->validationRules(MediaCollection::Gallery),
        ]);

        /** @var array<int, UploadedFile> $photos */
        $photos = $request->file('photos') ?? [];

        $order = (int) $album->images()->max('display_order');

        foreach (array_slice($photos, 0, self::MAX_UPLOAD) as $photo) {
            $media = $this->media->store($photo, MediaCollection::Gallery, $album);

            GalleryImage::query()->create([
                'gallery_album_id' => $album->id,
                'media_id' => $media->id,
                'display_order' => ++$order,
            ]);
        }

        $this->syncCount($album);

        return back()->with('success', __('admin.gallery.uploaded'));
    }

    public function updateImage(Request $request, GalleryImage $image): RedirectResponse
    {
        $image->update($request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]));

        return back()->with('success', __('common.states.saved'));
    }

    public function destroyImage(GalleryImage $image): RedirectResponse
    {
        $album = $image->album;

        // The file goes with the row. An orphaned upload is storage nobody
        // will ever account for, and it stays reachable by its URL.
        $this->media->delete($image->media, force: true);

        $image->delete();

        $this->syncCount($album);

        return back()->with('success', __('admin.gallery.image_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function syncCount(GalleryAlbum $album): void
    {
        $album->forceFill(['images_count' => $album->images()->count()])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(GalleryAlbum $album): array
    {
        return [
            'id' => $album->id,
            'slug' => $album->slug,
            'title' => $album->title,
            'description' => $album->description,
            'event_id' => $album->event_id,
            'event' => $album->event?->title,
            'batch_id' => $album->batch_id,
            'batch' => $album->batch?->name,
            'status' => $album->status->value,
            'status_label' => $album->status->label(),
            'published_at' => $album->published_at?->toIso8601String(),
            'images_count' => $album->images_count,
            'display_order' => $album->display_order,
            'cover_url' => $album->cover_path === null ? null : asset('storage/'.$album->cover_path),
            'url' => route('gallery.show', $album->slug, absolute: false),
        ];
    }
}
