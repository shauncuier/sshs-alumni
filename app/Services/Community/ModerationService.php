<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\ReportStatus;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Every moderation action, in one place, and every one of them audited.
 *
 * WHY THE AUDIT ROW IS WRITTEN HERE AND NOT BY AN OBSERVER. The interesting
 * fact about a moderation action is not that `status` changed from `published`
 * to `hidden` — a column diff carries that. It is WHO decided, and WHY, and
 * which report they were working from. That context exists only at the moment
 * the moderator acts, so it is captured at that moment or not at all.
 *
 * Posts and comments are NOT `Auditable`. If they were, every member editing
 * their own typo would write an audit row and the moderation record would be
 * buried in ordinary traffic.
 *
 * HIDDEN IS NOT REMOVED. Hiding takes something out of the feed and leaves it
 * readable by the author and by moderators; removing marks it gone for
 * everyone. Both are reversible, and neither deletes the row — a member who
 * complains that their post disappeared is owed an answer, and an empty table
 * cannot give one.
 *
 * @see docs/05-modules.md section 11
 */
class ModerationService
{
    public function setPostStatus(Post $post, PostStatus $status, User $actor, ?string $reason = null): Post
    {
        $from = $post->status;

        if ($from === $status) {
            return $post;
        }

        $post->forceFill(['status' => $status])->save();

        $this->audit($post, "post.{$status->value}", $actor, $reason, [
            'from' => $from->value,
            'to' => $status->value,
        ]);

        return $post;
    }

    public function setCommentStatus(Comment $comment, CommentStatus $status, User $actor, ?string $reason = null): Comment
    {
        $from = $comment->status;

        if ($from === $status) {
            return $comment;
        }

        $comment->forceFill(['status' => $status])->save();

        $this->audit($comment, "comment.{$status->value}", $actor, $reason, [
            'from' => $from->value,
            'to' => $status->value,
        ]);

        return $comment;
    }

    /**
     * Pin a post to the top of the feed, or unpin it.
     */
    public function setPinned(Post $post, bool $pinned, User $actor): Post
    {
        if ($post->is_pinned === $pinned) {
            return $post;
        }

        $post->forceFill(['is_pinned' => $pinned])->save();

        $this->audit($post, $pinned ? 'post.pinned' : 'post.unpinned', $actor, null, [
            'is_pinned' => $pinned,
        ]);

        return $post;
    }

    /**
     * Close a thread without hiding it.
     *
     * The useful middle setting: a post that has run its course, or turned
     * bad-tempered, stays readable while the argument stops.
     */
    public function setCommentsEnabled(Post $post, bool $enabled, User $actor): Post
    {
        if ($post->comments_enabled === $enabled) {
            return $post;
        }

        $post->forceFill(['comments_enabled' => $enabled])->save();

        $this->audit($post, $enabled ? 'post.comments_opened' : 'post.comments_closed', $actor, null, [
            'comments_enabled' => $enabled,
        ]);

        return $post;
    }

    /**
     * Close a report, with or without acting on the content.
     *
     * `dismissed` is a first-class outcome, not a failure: most reports of a
     * heated batch argument are somebody wanting the argument to stop, and
     * recording that a moderator looked and decided nothing was wrong is
     * exactly as valuable as recording a removal.
     *
     * Every OTHER open report against the same item closes with it, so two
     * moderators cannot work the same post twice.
     */
    public function resolveReport(
        ContentReport $report,
        ReportStatus $status,
        User $actor,
        ?string $note = null,
    ): ContentReport {
        return DB::transaction(function () use ($report, $status, $actor, $note): ContentReport {
            $report->forceFill([
                'status' => $status,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_note' => $note,
            ])->save();

            if ($status->isClosed()) {
                ContentReport::query()
                    ->where('reportable_type', $report->reportable_type)
                    ->where('reportable_id', $report->reportable_id)
                    ->whereNot('id', $report->id)
                    ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
                    ->update([
                        'status' => $status->value,
                        'resolved_by' => $actor->id,
                        'resolved_at' => now(),
                        'resolution_note' => $note,
                        'updated_at' => now(),
                    ]);
            }

            $this->audit($report, "report.{$status->value}", $actor, $note, [
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
            ]);

            return $report;
        });
    }

    /**
     * @param  array<string, mixed>  $after
     */
    private function audit(Model $subject, string $action, User $actor, ?string $reason, array $after): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => "moderation.{$action}",
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'before' => null,
            'after' => $after,
            'description' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => str(Request::userAgent() ?? '')->limit(500)->toString() ?: null,
        ]);
    }
}
