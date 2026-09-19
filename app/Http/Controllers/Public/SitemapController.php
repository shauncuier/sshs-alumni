<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AlumniStory;
use App\Models\Batch;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for public search engine indexing.
     */
    public function index(): Response
    {
        $urls = [];

        // 1. Static Public Pages
        $staticRoutes = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('about'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('about.school'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('jubilee'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('events.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('batches.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('news.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('announcements.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('gallery.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('committees.index'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('stories.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('donate'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('sponsorship'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($staticRoutes as $route) {
            $urls[] = [
                'loc' => $route['loc'],
                'lastmod' => now()->toAtomString(),
                'changefreq' => $route['changefreq'],
                'priority' => $route['priority'],
            ];
        }

        // 2. Published News
        foreach (News::query()->published()->latest('published_at')->limit(100)->get() as $news) {
            $urls[] = [
                'loc' => route('news.show', $news->slug),
                'lastmod' => $news->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // 3. Published Events
        foreach (Event::query()->published()->latest()->limit(50)->get() as $event) {
            $urls[] = [
                'loc' => route('events.show', $event->slug),
                'lastmod' => $event->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 4. Batches
        foreach (Batch::query()->orderBy('ssc_year')->get() as $batch) {
            $urls[] = [
                'loc' => route('batches.show', $batch->slug),
                'lastmod' => $batch->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // 5. Published Gallery Albums
        foreach (GalleryAlbum::query()->published()->latest('published_at')->limit(50)->get() as $album) {
            $urls[] = [
                'loc' => route('gallery.show', $album->slug),
                'lastmod' => $album->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // 6. Published Alumni Stories
        foreach (AlumniStory::query()->published()->latest('published_at')->limit(50)->get() as $story) {
            $urls[] = [
                'loc' => route('stories.show', $story->slug),
                'lastmod' => $story->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // 7. Standing Pages
        foreach (Page::query()->published()->get() as $page) {
            $urls[] = [
                'loc' => route('pages.show', $page->slug),
                'lastmod' => $page->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    /**
     * Generate dynamic robots.txt directives.
     */
    public function robots(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        $content = <<<TXT
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /my/
Disallow: /directory/
Disallow: /verify/
Disallow: /join/
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /two-factor-challenge

Sitemap: {$sitemapUrl}
TXT;

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
