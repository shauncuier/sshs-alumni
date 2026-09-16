<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Enums\MediaCollection;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Stores uploads, re-encodes images and records them in the media table.
 *
 * Security rules enforced here rather than left to the caller:
 *
 *  - The file's REAL mime type is checked, never the client-supplied one.
 *  - Raster images are re-encoded, which strips EXIF — including the GPS
 *    coordinates phones embed in photos — and neutralises polyglot files that
 *    are a valid image and a valid script at once.
 *  - SVG is sanitised: scripts, event handlers and external references are
 *    removed before storage.
 *  - Files are stored under a generated name. The original filename is kept
 *    for display only and never used as a path.
 *  - Collections marked private go to a disk that is never served by URL.
 *
 * @see docs/08-security-privacy.md section 5
 */
class MediaService
{
    public function __construct(
        private readonly ImageVariantGenerator $variants,
    ) {}

    /**
     * Store an upload and return its media record.
     */
    public function store(
        UploadedFile $file,
        MediaCollection $collection,
        ?Model $model = null,
        ?string $alt = null,
    ): Media {
        $rules = $this->rules($collection);

        $this->assertAllowed($file, $collection, $rules);

        $isPublic = $rules['disk'] === 'public';
        $disk = $isPublic
            ? (string) config('media.public_disk')
            : (string) config('media.private_disk');

        $extension = $this->safeExtension($file);
        $directory = $collection->value.'/'.now()->format('Y/m');
        $name = (string) Str::ulid();
        $path = "{$directory}/{$name}.{$extension}";

        [$storedPath, $thumbPath, $width, $height] = $this->write(
            $file,
            $disk,
            $path,
            $rules,
        );

        return Media::query()->create([
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->getKey(),
            'collection' => $collection,
            'disk' => $disk,
            'path' => $storedPath,
            'thumb_path' => $thumbPath,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => (string) $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'is_public' => $isPublic,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * A URL for public media. Private media returns null — it must be served
     * through an authorised controller, never linked directly.
     */
    public function url(Media $media, bool $thumb = false): ?string
    {
        if (! $media->is_public) {
            return null;
        }

        $path = $thumb && $media->thumb_path !== null
            ? $media->thumb_path
            : $media->path;

        return Storage::disk($media->disk)->url($path);
    }

    /**
     * Delete the stored files alongside the record.
     */
    public function delete(Media $media, bool $force = false): void
    {
        $disk = Storage::disk($media->disk);

        $disk->delete($media->path);

        if ($media->thumb_path !== null) {
            $disk->delete($media->thumb_path);
        }

        $force ? $media->forceDelete() : $media->delete();
    }

    /**
     * Laravel validation rules for a collection, so Form Requests and the
     * service agree on what is acceptable.
     *
     * @return array<int, string>
     */
    public function validationRules(MediaCollection $collection): array
    {
        $rules = $this->rules($collection);

        return [
            'file',
            'mimes:'.implode(',', $rules['mimes']),
            'max:'.$rules['max_kb'],
        ];
    }

    /**
     * @param  array{disk: string, mimes: array<int, string>, max_kb: int, max_dimension: int|null, thumb: int|null}  $rules
     * @return array{0: string, 1: string|null, 2: int|null, 3: int|null}
     */
    private function write(UploadedFile $file, string $disk, string $path, array $rules): array
    {
        $extension = $this->safeExtension($file);

        // SVG is never rasterised; it is sanitised as text.
        if ($extension === 'svg') {
            $svg = SvgSanitizer::clean((string) file_get_contents($file->getRealPath()));
            Storage::disk($disk)->put($path, $svg);

            return [$path, null, null, null];
        }

        if (! $this->isRasterImage($file)) {
            Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path),
            );

            return [$path, null, null, null];
        }

        return $this->variants->generate($file, $disk, $path, $rules);
    }

    /**
     * @param  array{disk: string, mimes: array<int, string>, max_kb: int, max_dimension: int|null, thumb: int|null}  $rules
     */
    private function assertAllowed(UploadedFile $file, MediaCollection $collection, array $rules): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The upload failed before it reached the server.');
        }

        $extension = $this->safeExtension($file);

        if (! in_array($extension, $rules['mimes'], true)) {
            throw new InvalidArgumentException(
                "Files of type .{$extension} are not allowed in the {$collection->value} collection."
            );
        }

        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);

        if ($sizeKb > $rules['max_kb']) {
            throw new InvalidArgumentException(
                "The file is {$sizeKb}KB; the limit for {$collection->value} is {$rules['max_kb']}KB."
            );
        }
    }

    /**
     * The extension derived from the file's REAL type, falling back to the
     * client-supplied one only when the server cannot guess.
     *
     * A .php renamed to .jpg is caught here: guessExtension() reads the actual
     * contents, so it reports the real type and the allow-list rejects it.
     */
    private function safeExtension(UploadedFile $file): string
    {
        $guessed = $file->guessExtension();

        $extension = Str::lower($guessed ?: $file->getClientOriginalExtension());

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private function isRasterImage(UploadedFile $file): bool
    {
        return in_array(
            $this->safeExtension($file),
            ['jpg', 'png', 'webp', 'gif'],
            true,
        );
    }

    /**
     * @return array{disk: string, mimes: array<int, string>, max_kb: int, max_dimension: int|null, thumb: int|null}
     */
    private function rules(MediaCollection $collection): array
    {
        /** @var array{disk: string, mimes: array<int, string>, max_kb: int, max_dimension: int|null, thumb: int|null}|null $rules */
        $rules = config('media.collections.'.$collection->value);

        if ($rules === null) {
            throw new InvalidArgumentException(
                "No media rules configured for collection [{$collection->value}]."
            );
        }

        return $rules;
    }
}
