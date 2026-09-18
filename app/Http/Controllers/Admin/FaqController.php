<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\FaqGroup;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Frequently asked questions, grouped.
 *
 * The Jubilee group is what the microsite renders, and it is seeded with the
 * questions the committee will actually be asked — when it is, what it costs,
 * whether families may come. An FAQ page that answers questions nobody asked
 * is filler; these were written from the event's own unknowns.
 *
 * Unpublishing keeps the row. A question that stops being relevant this year
 * is usually relevant again next year.
 *
 * @see database/seeders/JubileeSeeder.php
 */
class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $faqs = Faq::query()
            ->orderBy('group')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/faqs/index', [
            'faqs' => $faqs->map(fn (Faq $faq): array => $this->row($faq))->all(),
            'options' => ['groups' => FaqGroup::options()],
            'can' => [
                'manage' => $request->user()?->can('content.manage') ?? false,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Faq::query()->create($this->validated($request));

        return back()->with('success', __('common.states.saved'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return back()->with('success', __('common.states.saved'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', __('admin.content.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'group' => ['required', Rule::in(FaqGroup::values())],
            'question' => ['required', 'string', 'max:300'],
            'answer' => ['required', 'string', 'max:5000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'group' => $faq->group->value,
            'group_label' => $faq->group->label(),
            'question' => $faq->question,
            'answer' => $faq->answer,
            'display_order' => $faq->display_order,
            'is_published' => $faq->is_published,
        ];
    }
}
