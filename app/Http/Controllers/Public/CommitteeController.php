<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CommitteeMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\CommitteeMember;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who runs the association.
 *
 * Serving members only — a past committee is part of the record but this page
 * answers "who do I contact now".
 *
 * Contact details are shown because a committee member's role IS to be
 * contactable. That is a different thing from an ordinary member's phone
 * number, which the directory hides by default.
 *
 * @see docs/05-modules.md section 10
 */
class CommitteeController extends Controller
{
    public function __invoke(): Response
    {
        $committees = Committee::query()
            ->where('status', 'active')
            ->with([
                'members' => fn ($query) => $query
                    ->where('status', CommitteeMemberStatus::Active)
                    ->orderBy('display_order'),
            ])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('public/committees', [
            'committees' => $committees
                ->map(fn (Committee $committee): array => [
                    'slug' => $committee->slug,
                    'name' => $committee->name,
                    'type_label' => $committee->type->label(),
                    'description' => $committee->description,
                    'term' => $this->term($committee),
                    'members' => $committee->members
                        ->map(fn (CommitteeMember $member): array => [
                            'name' => $member->name,
                            'role' => $member->role,
                            'designation' => $member->designation,
                            'photo_url' => $member->photo_path === null
                                ? null
                                : asset('storage/'.$member->photo_path),
                            'contact_email' => $member->contact_email,
                            'contact_phone' => $member->contact_phone,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
        ]);
    }

    /**
     * "2025 — 2027", or nothing when the dates are not set.
     */
    private function term(Committee $committee): ?string
    {
        if ($committee->term_start === null) {
            return null;
        }

        $start = $committee->term_start->year;
        $end = $committee->term_end?->year;

        return $end === null ? (string) $start : "{$start} — {$end}";
    }
}
