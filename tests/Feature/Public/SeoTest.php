<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Enums\EventStatus;
use App\Models\Batch;
use App\Models\Event;
use App\Models\News;
use App\Models\Page;

it('generates an XML sitemap with static and published entity URLs', function (): void {
    $news = News::factory()->create([
        'slug' => 'sitemap-sample-news',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    $event = Event::factory()->create([
        'slug' => 'sitemap-sample-event',
        'status' => EventStatus::Published,
    ]);
    $batch = Batch::factory()->create(['slug' => 'sitemap-batch-2001', 'ssc_year' => 2001]);
    $page = Page::factory()->create([
        'slug' => 'sitemap-terms',
        'status' => ContentStatus::Published,
    ]);

    $response = $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $content = $response->getContent();

    expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($content)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->and($content)->toContain(route('home'))
        ->and($content)->toContain(route('about'))
        ->and($content)->toContain(route('jubilee'))
        ->and($content)->toContain(route('news.show', $news->slug))
        ->and($content)->toContain(route('events.show', $event->slug))
        ->and($content)->toContain(route('batches.show', $batch->slug))
        ->and($content)->toContain(route('pages.show', $page->slug));
});

it('generates a robots.txt with disallow rules and a sitemap link', function (): void {
    $response = $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $content = $response->getContent();

    expect($content)->toContain('User-agent: *')
        ->and($content)->toContain('Disallow: /admin/')
        ->and($content)->toContain('Disallow: /my/')
        ->and($content)->toContain('Disallow: /directory/')
        ->and($content)->toContain('Disallow: /verify/')
        ->and($content)->toContain('Disallow: /join/')
        ->and($content)->toContain('Sitemap: '.url('/sitemap.xml'));
});
