<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reporting a post or a comment.
 *
 * REPORTING IS NOT MODERATION. Filing a report hides nothing and tells nobody
 * else: it puts the item in a queue for somebody with `community.moderate` to
 * look at. If a report took content down on its own, the community would have
 * handed anybody with a grudge a delete button.
 *
 * The same member reporting the same item twice updates nothing and creates
 * nothing — the second report tells a moderator no more than the first.
 *
 * @see docs/05-modules.md section 11
 */
class ContentReportController extends Controller
{
    public function post(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('report', $post);

        return $this->file($request, $post);
    }

    public function comment(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('report', $comment);

        return $this->file($request, $comment);
    }

    private function file(Request $request, Model $reportable): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', ReportReason::values())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var Member $member */
        $member = $request->user()?->member;

        $existing = ContentReport::query()
            ->where('reportable_type', $reportable->getMorphClass())
            ->where('reportable_id', $reportable->getKey())
            ->where('reporter_member_id', $member->id)
            ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
            ->exists();

        if (! $existing) {
            ContentReport::query()->create([
                'reportable_type' => $reportable->getMorphClass(),
                'reportable_id' => $reportable->getKey(),
                'reporter_member_id' => $member->id,
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
            ]);
        }

        // The same message either way. A reporter learning that somebody else
        // already reported this post learns something about another member's
        // opinion of it, which is not theirs to know.
        return back()->with('success', __('member.community.reported'));
    }
}
