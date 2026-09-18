<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The author of a post or comment, as the community may see them.
 *
 * WHERE THE PRIVACY LINE FALLS HERE, and why it is not the same line as the
 * directory's. Posting is a public act inside the community: you cannot write
 * under a name nobody may see, so the NAME is always present. What the member
 * controls is everything that follows from it — whether their face appears
 * beside their words, and whether their name is a door into the profile they
 * chose to close.
 *
 * So: name always; `photo_url` and `url` only when `show_profile` is on, and
 * ABSENT rather than null when it is not, in keeping with the rest of the
 * resource layer.
 *
 * @see docs/08-security-privacy.md section 2
 *
 * @mixin Member
 */
class CommunityAuthorResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // MemberObserver creates the privacy row for every member, so it is
        // always there by the time anybody can post.
        $visible = (bool) $this->privacy->show_profile;

        return [
            'ulid' => $this->ulid,
            'name' => $this->full_name,
            'batch' => $this->whenLoaded('batch', fn (): ?string => $this->batch?->name),

            $this->mergeWhen($visible, fn (): array => [
                'photo_url' => $this->photo_path === null
                    ? null
                    : asset('storage/'.$this->photo_path),
                'url' => route('directory.show', $this->ulid, absolute: false),
            ]),
        ];
    }
}
