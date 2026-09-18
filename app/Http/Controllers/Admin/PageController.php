<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Support\SlugFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Standing pages — privacy policy, terms, and whatever else the committee
 * needs a URL for.
 *
 * SYSTEM PAGES CANNOT BE DELETED. The privacy policy and the terms are seeded
 * with `is_system`, and the destroy endpoint refuses them with a 403 rather
 * than hiding the button — hiding it in the UI would leave the route open to
 * anybody who guessed it, and a deleted privacy policy is a legal problem
 * rather than a content one.
 *
 * Their slugs are fixed for the same reason: the footer links to
 * `/p/privacy-policy`, and so does every registration form anybody has ever
 * submitted.
 *
 * @see database/seeders/ReferenceDataSeeder.php
 */
class PageController extends Controller
{
    public function index(Request $request): Response
    {
        $pages = Page::query()
            ->orderByDesc('is_system')
            ->orderBy('title')
            ->get();

        return Inertia::render('admin/pages/index', [
            'pages' => $pages->map(fn (Page $page): array => $this->row($page))->all(),
            'options' => ['statuses' => ContentStatus::options()],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
                'publish' => $request->user()?->can('content.publish') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        /** @var User $actor */
        $actor = $request->user();

        Page::query()->create([
            ...$validated,
            'slug' => SlugFactory::unique(Page::class, $validated['title'], 'page'),
            'status' => ContentStatus::Draft,
            'updated_by' => $actor->id,
        ]);

        return back()->with('success', __('admin.content.saved_draft'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $page->update([
            ...$this->validated($request),
            'updated_by' => $actor->id,
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function publish(Request $request, Page $page): RedirectResponse
    {
        $publish = $request->boolean('publish', true);

        $publish ? $page->publish() : $page->unpublish();

        return back()->with('success', $publish
            ? __('admin.content.published')
            : __('admin.content.unpublished'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        // Enforced here, not only hidden in the UI. A hidden button is not a
        // rule; it is a suggestion to anybody who can type a URL.
        abort_if($page->is_system, 403, __('admin.content.system_page'));

        $page->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Page $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'body' => $page->body,
            'status' => $page->status->value,
            'status_label' => $page->status->label(),
            'is_system' => $page->is_system,
            'published_at' => $page->published_at?->toIso8601String(),
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'updated_by' => $page->editor?->name,
            'url' => route('pages.show', $page->slug, absolute: false),
        ];
    }
}
