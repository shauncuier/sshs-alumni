<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

/**
 * Re-encodes uploaded images and writes a thumbnail.
 *
 * Re-encoding is a security measure as much as a size one: it strips EXIF —
 * including the GPS coordinates phones embed in photos, which members do not
 * expect to publish with a profile picture — and it neutralises polyglot files
 * that are simultaneously a valid image and a valid script.
 *
 * @see docs/08-security-privacy.md section 5
 */
class ImageVariantGenerator
{
    private ImageManager $manager;

    public function __construct()
    {
        // GD rather than Imagick: it is present on far more shared hosts, and
        // nothing here needs Imagick's extra formats.
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Write the image and its thumbnail, returning their paths and dimensions.
     *
     * @param  array{disk: string, mimes: array<int, string>, max_kb: int, max_dimension: int|null, thumb: int|null}  $rules
     * @return array{0: string, 1: string|null, 2: int, 3: int}
     */
    public function generate(UploadedFile $file, string $disk, string $path, array $rules): array
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Intervention Image 4.3 renamed read() to decodePath() and
        // encodeByExtension() to encodeUsingFileExtension().
        //
        // A file that cannot be decoded is not an image, whatever its
        // extension claims — that includes a script renamed to .jpg that got
        // past the mime guesser. Rethrow as a domain error so callers handle
        // one exception type and the member sees a validation message rather
        // than a decoder stack trace.
        try {
            $image = $this->manager->decodePath($file->getRealPath());
        } catch (DecoderException $e) {
            throw new InvalidArgumentException(
                'That file is not a readable image.',
                previous: $e,
            );
        }

        // Cap the longest edge. This also defuses decompression-bomb images,
        // which are small on disk and enormous in memory.
        if ($rules['max_dimension'] !== null) {
            $image->scaleDown(
                width: $rules['max_dimension'],
                height: $rules['max_dimension'],
            );
        }

        $encoded = $image->encodeUsingFileExtension($extension, quality: 85);

        Storage::disk($disk)->put($path, (string) $encoded);

        $thumbPath = null;

        if ($rules['thumb'] !== null) {
            $thumbPath = $this->thumbPathFor($path);

            $thumb = $this->manager->decodePath($file->getRealPath())
                ->scaleDown(width: $rules['thumb'], height: $rules['thumb'])
                ->encodeUsingFileExtension($extension, quality: 80);

            Storage::disk($disk)->put($thumbPath, (string) $thumb);
        }

        return [$path, $thumbPath, $image->width(), $image->height()];
    }

    private function thumbPathFor(string $path): string
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return "{$directory}/{$name}-thumb.{$extension}";
    }
}
