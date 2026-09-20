<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ContentStatus;
use App\Models\Announcement;
use App\Models\GalleryAlbum;
use App\Models\News;

it('serves published news articles and single article', function (): void {
    $published = News::factory()->create([
        'title' => 'Published Article',
        'slug' => 'published-article',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $draft = News::factory()->create([
        'title' => 'Draft Article',
        'slug' => 'draft-article',
        'status' => ContentStatus::Draft,
        'published_at' => null,
    ]);

    // List news
    $response = $this->getJson(route('api.news.index'));

    $response->assertOk()
        ->assertJsonStructure(['news' => ['data', 'links', 'meta']])
        ->assertJsonPath('news.data.0.slug', 'published-article');

    // Published article detail
    $showResponse = $this->getJson(route('api.news.show', $published->slug));
    $showResponse->assertOk()
        ->assertJsonPath('article.title', 'Published Article');

    // Draft article detail returns 404
    $this->getJson(route('api.news.show', $draft->slug))
        ->assertNotFound();
});

it('serves only live and published announcements', function (): void {
    Announcement::factory()->create([
        'title' => 'Live Notice',
        'status' => ContentStatus::Published,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    Announcement::factory()->create([
        'title' => 'Expired Notice',
        'status' => ContentStatus::Published,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->getJson(route('api.announcements.index'));

    $response->assertOk()
        ->assertJsonCount(1, 'announcements.data')
        ->assertJsonPath('announcements.data.0.title', 'Live Notice');
});

it('serves published gallery albums and album with images', function (): void {
    $album = GalleryAlbum::factory()->create([
        'title' => 'Golden Jubilee Photos',
        'slug' => 'golden-jubilee-photos',
        'status' => ContentStatus::Published,
    ]);

    $draftAlbum = GalleryAlbum::factory()->create([
        'title' => 'Unpublished Album',
        'slug' => 'unpublished-album',
        'status' => ContentStatus::Draft,
    ]);

    // List albums
    $response = $this->getJson(route('api.gallery.index'));

    $response->assertOk()
        ->assertJsonStructure(['albums' => ['data', 'links', 'meta']])
        ->assertJsonPath('albums.data.0.slug', 'golden-jubilee-photos');

    // Show album
    $showResponse = $this->getJson(route('api.gallery.show', $album->slug));
    $showResponse->assertOk()
        ->assertJsonPath('album.title', 'Golden Jubilee Photos');

    // Draft album returns 404
    $this->getJson(route('api.gallery.show', $draftAlbum->slug))
        ->assertNotFound();
});
