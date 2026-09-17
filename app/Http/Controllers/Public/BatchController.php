<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public batch pages.
 *
 * AGGREGATE COUNTS ONLY. No member names, no photos, no list.
 *
 * The directory is members-only, and a public page that enumerated real alumni
 * names and batch years would hand a scraper exactly what the directory
 * withholds. The counts come from the cached column, so this page costs one
 * query regardless of how many batches exist.
 *
 * @see docs/03-routes.md section 1
 */
class BatchController extends Controller
{
    public function index(): Response
    {
        $batches = Batch::query()
            ->where('status', 'active')
            ->orderByDesc('ssc_year')
            ->get(['id', 'slug', 'name', 'name_bn', 'ssc_year', 'members_count', 'cover_path']);

        return Inertia::render('public/batches', [
            'batches' => $batches->map(fn (Batch $batch): array => [
                'slug' => $batch->slug,
                'name' => $batch->getTranslation('name') ?? $batch->name,
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ]),
            'totals' => [
                'batches' => $batches->count(),
                'members' => $batches->sum('members_count'),
            ],
        ]);
    }

    public function show(Batch $batch): Response
    {
        return Inertia::render('public/batch-show', [
            'batch' => [
                'slug' => $batch->slug,
                'name' => $batch->getTranslation('name') ?? $batch->name,
                'description' => $batch->getTranslation('description'),
                'ssc_year' => $batch->ssc_year,
                'members_count' => $batch->members_count,
                'cover_url' => $batch->cover_path === null
                    ? null
                    : asset('storage/'.$batch->cover_path),
            ],
        ]);
    }
}
