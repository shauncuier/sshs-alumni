<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A comment, threaded one level.
 *
 * One level, not arbitrary depth: a thread that nests forever is unreadable on
 * a phone, which is where most of this association reads it. A reply to a
 * reply attaches to the same parent.
 *
 * The body is sent raw and rendered as text — see PostResource.
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => CommunityAuthorResource::make($this->whenLoaded('author')),

            'reactions_count' => $this->whenLoaded(
                'reactions',
                fn (): int => $this->reactions->count(),
            ),

            'my_reaction' => $this->whenLoaded(
                'reactions',
                fn (): ?string => $this->reactions
                    ->firstWhere('member_id', $request->user()?->member?->id)
                    ?->type->value,
            ),

            'reaction_counts' => $this->whenLoaded(
                'reactions',
                fn (): array => $this->reactions
                    ->groupBy(fn (Reaction $reaction): string => $reaction->type->value)
                    ->map(fn ($group): int => $group->count())
                    ->all(),
            ),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'can' => [
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'report' => $request->user()?->can('report', $this->resource) ?? false,
            ],
        ];
    }
}
