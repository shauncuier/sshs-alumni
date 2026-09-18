<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community post as one member sees it.
 *
 * The body is sent RAW and rendered as text on the client — never as HTML.
 * Posts are member-authored, so anything that reached a `dangerouslySet`
 * would be stored cross-site scripting written by whoever wanted it. Mentions
 * are the one thing that becomes a link, and they are matched against a fixed
 * `@member:{ulid}` pattern, never against arbitrary markup.
 *
 * @see docs/08-security-privacy.md section 4
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user()?->member;

        return [
            'ulid' => $this->ulid,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'batch' => $this->whenLoaded('batch', fn (): ?string => $this->batch?->name),
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_pinned' => $this->is_pinned,
            'comments_enabled' => $this->comments_enabled,
            'comments_count' => $this->comments_count,
            'reactions_count' => $this->reactions_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),

            'author' => CommunityAuthorResource::make($this->whenLoaded('author')),

            'photos' => $this->whenLoaded('media', fn (): array => $this->media
                ->map(fn (Media $media): array => [
                    'url' => asset('storage/'.$media->path),
                    'thumb_url' => $media->thumb_path === null
                        ? asset('storage/'.$media->path)
                        : asset('storage/'.$media->thumb_path),
                    'alt' => $media->alt,
                ])
                ->all()),

            // What this reader pressed, so the button can show it already
            // pressed instead of making them guess.
            'my_reaction' => $this->whenLoaded(
                'reactions',
                fn (): ?string => $this->reactions
                    ->firstWhere('member_id', $viewer?->id)
                    ?->type->value,
            ),

            'reaction_counts' => $this->whenLoaded(
                'reactions',
                fn (): array => $this->reactions
                    ->groupBy(fn (Reaction $reaction): string => $reaction->type->value)
                    ->map(fn ($group): int => $group->count())
                    ->all(),
            ),

            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'report' => $request->user()?->can('report', $this->resource) ?? false,
            ],
        ];
    }
}
