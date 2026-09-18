<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollection;
use App\Http\Controllers\Controller;
use App\Models\SchoolMilestone;
use App\Services\Media\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The school's timeline.
 *
 * Seeded with two milestones and left open: 1976, when the school opened, and
 * 2015, when the association was founded. Everything between them is the
 * committee's to fill in, and the seeder says so rather than inventing a
 * history nobody verified.
 *
 * Ordered by YEAR first and `display_order` only within a year, because a
 * timeline is a sequence of years before it is a list somebody arranged.
 *
 * @see database/seeders/SchoolHistorySeeder.php
 */
class HistoryController extends Controller
{
    /**
     * The school opened in 1976; anything earlier is a typo, and a milestone
     * more than a year out is somebody entering a date rather than a year.
     */
    private const EARLIEST_YEAR = 1976;

    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $milestones = SchoolMilestone::query()
            ->orderBy('year')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('admin/history/index', [
            'milestones' => $milestones
                ->map(fn (SchoolMilestone $milestone): array => $this->row($milestone))
                ->all(),
            'options' => [
                'earliest_year' => self::EARLIEST_YEAR,
                'latest_year' => (int) now()->addYear()->year,
            ],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $milestone = SchoolMilestone::query()->create($this->validated($request));

        $this->attachImage($request, $milestone);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, SchoolMilestone $milestone): RedirectResponse
    {
        $milestone->update($this->validated($request));

        $this->attachImage($request, $milestone);

        return back()->with('success', __('common.states.saved'));
    }

    public function destroy(SchoolMilestone $milestone): RedirectResponse
    {
        $milestone->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'year' => ['required', 'integer', 'min:'.self::EARLIEST_YEAR, 'max:'.now()->addYear()->year],
            'date_label' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_highlighted' => ['nullable', 'boolean'],
        ]);
    }

    private function attachImage(Request $request, SchoolMilestone $milestone): void
    {
        $image = $request->file('image');

        if (! $image instanceof UploadedFile) {
            return;
        }

        $request->validate([
            'image' => $this->media->validationRules(MediaCollection::Gallery),
        ]);

        $media = $this->media->store($image, MediaCollection::Gallery, $milestone);

        $milestone->update(['image_path' => $media->path]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(SchoolMilestone $milestone): array
    {
        return [
            'id' => $milestone->id,
            'year' => $milestone->year,
            'date_label' => $milestone->date_label,
            'title' => $milestone->title,
            'description' => $milestone->description,
            'display_order' => $milestone->display_order,
            'is_highlighted' => $milestone->is_highlighted,
            'image_url' => $milestone->image_path === null
                ? null
                : asset('storage/'.$milestone->image_path),
        ];
    }
}
