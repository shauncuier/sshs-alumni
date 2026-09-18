import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import type { MentionMap } from '@/types/community';

/**
 * `@member:{ulid}` — 26 Crockford base32 characters. Must match
 * App\Services\Community\MentionParser::PATTERN.
 */
const MENTION = /@member:([0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26})/g;

type Props = {
    body: string;
    mentions: MentionMap;
};

/**
 * Member-written text, rendered as TEXT.
 *
 * There is no `dangerouslySetInnerHTML` here and there must never be one. The
 * body is whatever a member typed; anything that rendered it as markup would
 * be stored cross-site scripting written by whoever wanted it.
 *
 * The only thing that becomes a link is a mention, and a mention is matched
 * against a fixed pattern and looked up in a map the server built — never
 * constructed from the text itself. A mention that does not resolve renders as
 * the literal characters the author typed, which is the honest failure: it says
 * somebody was mentioned without inventing who.
 *
 * @see app/Services/Community/MentionParser.php
 */
export function MentionText({ body, mentions }: Props) {
    const parts: Array<string | { key: string; ulid: string }> = [];
    let lastIndex = 0;

    for (const match of body.matchAll(MENTION)) {
        const index = match.index ?? 0;

        if (index > lastIndex) {
            parts.push(body.slice(lastIndex, index));
        }

        parts.push({ key: `${index}`, ulid: match[1].toUpperCase() });
        lastIndex = index + match[0].length;
    }

    parts.push(body.slice(lastIndex));

    return (
        <p className="text-sm break-words whitespace-pre-wrap">
            {parts.map((part, index) => {
                if (typeof part === 'string') {
                    return <Fragment key={index}>{part}</Fragment>;
                }

                const member = mentions[part.ulid];

                if (!member) {
                    return (
                        <Fragment
                            key={index}
                        >{`@member:${part.ulid}`}</Fragment>
                    );
                }

                if (!member.url) {
                    return (
                        <span
                            key={index}
                            className="text-brand-green-800 dark:text-brand-green-200 font-medium"
                        >
                            @{member.name}
                        </span>
                    );
                }

                return (
                    <Link
                        key={index}
                        href={member.url}
                        className="text-brand-green-800 dark:text-brand-green-200 font-medium hover:underline"
                    >
                        @{member.name}
                    </Link>
                );
            })}
        </p>
    );
}
