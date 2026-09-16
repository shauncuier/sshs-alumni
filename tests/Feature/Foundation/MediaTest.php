<?php

declare(strict_types=1);

use App\Enums\MediaCollection;
use App\Models\Member;
use App\Services\Media\MediaService;
use App\Services\Media\SvgSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

describe('storing uploads', function (): void {
    it('stores an image and records it', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('photo.jpg', 800, 600),
            MediaCollection::Profile,
        );

        expect($media->exists)->toBeTrue()
            ->and($media->collection)->toBe(MediaCollection::Profile)
            ->and($media->is_public)->toBeTrue();

        Storage::disk('public')->assertExists($media->path);
    });

    it('never stores under the original filename', function (): void {
        // A filename is attacker-controlled. It is kept for display only.
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('../../evil name.jpg', 400, 400),
            MediaCollection::Profile,
        );

        expect($media->path)->not->toContain('evil name')
            ->and($media->path)->not->toContain('..')
            ->and($media->original_name)->toContain('evil name');
    });

    it('writes a thumbnail for collections that want one', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('photo.jpg', 1000, 1000),
            MediaCollection::Profile,
        );

        expect($media->thumb_path)->not->toBeNull();
        Storage::disk('public')->assertExists($media->thumb_path);
    });

    it('caps the longest edge', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('huge.jpg', 4000, 3000),
            MediaCollection::Profile,
        );

        // Profile caps at 1200.
        expect($media->width)->toBeLessThanOrEqual(1200)
            ->and($media->height)->toBeLessThanOrEqual(1200);
    });

    it('attaches media to a model when given one', function (): void {
        $member = Member::factory()->create();

        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('photo.jpg'),
            MediaCollection::Profile,
            $member,
        );

        expect($media->model_id)->toBe($member->id)
            ->and($member->media()->count())->toBe(1);
    });
});

describe('upload restrictions', function (): void {
    it('rejects a PHP file renamed to .jpg', function (): void {
        // guessExtension() reads the actual contents, so the disguise fails.
        $file = UploadedFile::fake()->createWithContent(
            'shell.jpg',
            '<?php echo shell_exec($_GET["c"]); ?>',
        );

        expect(fn () => app(MediaService::class)->store($file, MediaCollection::Profile))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects a file type outside the collection allow-list', function (): void {
        expect(fn () => app(MediaService::class)->store(
            UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            MediaCollection::Profile,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects a file over the collection size limit', function (): void {
        // Profile caps at 2MB.
        expect(fn () => app(MediaService::class)->store(
            UploadedFile::fake()->image('big.jpg')->size(4096),
            MediaCollection::Profile,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('does not accept SVG for profile photos', function (): void {
        expect(fn () => app(MediaService::class)->store(
            UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            MediaCollection::Profile,
        ))->toThrow(InvalidArgumentException::class);
    });
});

describe('private media', function (): void {
    it('routes documents to the private disk', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('id-card.jpg'),
            MediaCollection::Document,
        );

        expect($media->is_public)->toBeFalse()
            ->and($media->disk)->toBe('local');

        Storage::disk('local')->assertExists($media->path);
        Storage::disk('public')->assertMissing($media->path);
    });

    it('never returns a direct URL for private media', function (): void {
        // Private files are served only through a controller that runs a
        // policy check.
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('id-card.jpg'),
            MediaCollection::Document,
        );

        expect(app(MediaService::class)->url($media))->toBeNull();
    });

    it('returns a URL for public media', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->image('photo.jpg'),
            MediaCollection::Profile,
        );

        expect(app(MediaService::class)->url($media))->toBeString();
    });
});

describe('SVG sanitisation', function (): void {
    it('strips script elements', function (): void {
        $dirty = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="5"/></svg>';

        $clean = SvgSanitizer::clean($dirty);

        expect($clean)->not->toContain('script')
            ->and($clean)->toContain('circle');
    });

    it('strips event handler attributes', function (): void {
        $dirty = '<svg xmlns="http://www.w3.org/2000/svg"><circle r="5" onload="alert(1)" onclick="evil()"/></svg>';

        $clean = SvgSanitizer::clean($dirty);

        expect($clean)->not->toContain('onload')
            ->and($clean)->not->toContain('onclick');
    });

    it('strips javascript: URLs', function (): void {
        $dirty = '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><circle r="5"/></a></svg>';

        $clean = SvgSanitizer::clean($dirty);

        expect($clean)->not->toContain('javascript:');
    });

    it('strips DOCTYPE and ENTITY declarations used for XXE', function (): void {
        $dirty = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';

        $clean = SvgSanitizer::clean($dirty);

        expect($clean)->not->toContain('ENTITY')
            ->and($clean)->not->toContain('etc/passwd');
    });

    it('returns nothing for unparseable input rather than guessing', function (): void {
        expect(SvgSanitizer::clean('<svg><broken'))->toBe('');
    });

    it('sanitises an SVG logo on upload', function (): void {
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->createWithContent(
                'logo.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10"/></svg>',
            ),
            MediaCollection::Logo,
        );

        $stored = Storage::disk('public')->get($media->path);

        expect($stored)->not->toContain('script')
            ->and($stored)->toContain('rect');
    });
});

describe('deletion', function (): void {
    it('removes the stored files as well as the record', function (): void {
        $service = app(MediaService::class);

        $media = $service->store(
            UploadedFile::fake()->image('photo.jpg'),
            MediaCollection::Profile,
        );

        $path = $media->path;
        $thumb = $media->thumb_path;

        $service->delete($media, force: true);

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumb);
    });
});
