<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FundraisingCampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
        ];

        $campaigns = FundraisingCampaign::query()
            ->withCount('donations')
            ->when($filters['q'] !== '', fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/fundraising/index', [
            'campaigns' => Paginated::from($campaigns, fn (FundraisingCampaign $c): array => [
                'id' => $c->id,
                'ulid' => $c->ulid,
                'slug' => $c->slug,
                'title' => $c->title,
                'tagline' => $c->tagline,
                'description' => $c->description,
                'goal_amount' => (float) $c->goal_amount,
                'raised_amount' => (float) $c->raised_amount,
                'progress_percentage' => $c->progressPercentage(),
                'donations_count' => $c->donations_count,
                'is_featured' => $c->is_featured,
                'status' => $c->status->value,
                'starts_at' => $c->starts_at?->toIso8601String(),
                'ends_at' => $c->ends_at?->toIso8601String(),
                'published_at' => $c->published_at?->toIso8601String(),
            ]),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'statuses' => ContentStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (FundraisingCampaign::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        FundraisingCampaign::create([
            ...$validated,
            'slug' => $slug,
            'raised_amount' => 0,
            'published_at' => $validated['status'] === ContentStatus::Published->value ? now() : null,
        ]);

        return back()->with('success', 'Fundraising campaign created successfully.');
    }

    public function update(Request $request, FundraisingCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ]);

        $campaign->update([
            ...$validated,
            'published_at' => $validated['status'] === ContentStatus::Published->value && ! $campaign->published_at ? now() : $campaign->published_at,
        ]);

        return back()->with('success', 'Fundraising campaign updated successfully.');
    }

    public function destroy(FundraisingCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('success', 'Fundraising campaign deleted.');
    }
}
