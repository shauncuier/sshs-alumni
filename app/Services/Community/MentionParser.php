<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * Mentions, stored inline and resolved at render time.
 *
 * A mention lives in the post body as `@member:{ulid}` — there is no
 * `post_mentions` table. A join table would add a write on every post for
 * something that is only ever read alongside the post it belongs to, and it
 * would drift the moment somebody edited their text.
 *
 * WHY THE ULID AND NOT THE NAME. Names are not unique in a school of fifty
 * years' alumni, and people change them. The ULID is stable, and a member who
 * later hides their profile stops being linkable without their old mentions
 * turning into dead links — the name still renders, the link does not.
 *
 * The parser NEVER returns a name for a ulid that does not resolve to an
 * approved member. An unresolvable token renders as the literal text the
 * author typed, which is the honest failure: it says a person was mentioned
 * without inventing who.
 *
 * @see docs/05-modules.md section 11
 */
class MentionParser
{
    /**
     * `@member:01J...` — 26 Crockford base32 characters.
     */
    public const PATTERN = '/@member:([0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26})/';

    /**
     * Every ULID mentioned in a body, in order, without duplicates.
     *
     * @return array<int, string>
     */
    public function extract(string $body): array
    {
        preg_match_all(self::PATTERN, $body, $matches);

        /** @var array<int, string> $ulids */
        $ulids = array_values(array_unique(array_map(
            static fn (string $ulid): string => strtoupper($ulid),
            $matches[1],
        )));

        return $ulids;
    }

    /**
     * Resolve every mention across many bodies in ONE query.
     *
     * The feed renders twenty posts and their comments; resolving each body on
     * its own would be a query per mention.
     *
     * @param  iterable<int, string>  $bodies
     * @return array<string, array{ulid: string, name: string, url: string|null}>
     */
    public function resolveMany(iterable $bodies): array
    {
        $ulids = [];

        foreach ($bodies as $body) {
            foreach ($this->extract($body) as $ulid) {
                $ulids[$ulid] = true;
            }
        }

        if ($ulids === []) {
            return [];
        }

        /** @var Collection<int, Member> $members */
        $members = Member::query()
            ->approved()
            ->with('privacy')
            ->whereIn('ulid', array_keys($ulids))
            ->get(['id', 'ulid', 'full_name']);

        $resolved = [];

        foreach ($members as $member) {
            $resolved[$member->ulid] = [
                'ulid' => $member->ulid,
                'name' => $member->full_name,
                // Linked only when the member lets the directory show them.
                // Somebody who has hidden their profile is still named — they
                // were part of the conversation — but not turned into a door
                // into a profile they closed.
                'url' => $member->privacy->show_profile
                    ? route('directory.show', $member->ulid, absolute: false)
                    : null,
            ];
        }

        return $resolved;
    }
}
