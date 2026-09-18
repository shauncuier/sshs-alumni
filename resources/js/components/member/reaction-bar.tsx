import { router } from '@inertiajs/react';
import { Heart, PartyPopper, ThumbsUp, Handshake } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';

const ICONS: Record<string, LucideIcon> = {
    like: ThumbsUp,
    love: Heart,
    celebrate: PartyPopper,
    support: Handshake,
};

const ORDER = ['like', 'love', 'celebrate', 'support'] as const;

type Props = {
    /** Where to POST the toggle. */
    url: string;
    mine?: string | null;
    counts?: Record<string, number>;
    total: number;
};

/**
 * The reaction row.
 *
 * Posts optimistically with `preserveScroll`, because a reaction that scrolls
 * the feed back to the top is worse than no reaction button at all. The server
 * decides what actually happened — tapping the reaction you already gave
 * removes it, tapping a different one changes it — and the unique index
 * decides what happens when two taps race.
 */
export function ReactionBar({ url, mine, counts = {}, total }: Props) {
    const { t, choice } = useTranslation();

    const react = (type: string) => {
        router.post(
            url,
            { type },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div className="flex flex-wrap items-center gap-1">
            {ORDER.map((type) => {
                const Icon = ICONS[type];
                const count = counts[type] ?? 0;
                const active = mine === type;

                return (
                    <button
                        key={type}
                        type="button"
                        onClick={() => react(type)}
                        aria-pressed={active}
                        aria-label={t(`enums.reaction_type.${type}`)}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs transition-colors',
                            active
                                ? 'bg-brand-green-100 text-brand-green-800 dark:bg-sidebar-accent dark:text-foreground'
                                : 'text-muted-foreground hover:bg-accent',
                        )}
                    >
                        <Icon className="size-3.5" aria-hidden="true" />
                        {count > 0 && (
                            <span className="tabular-nums">
                                {formatNumber(count)}
                            </span>
                        )}
                    </button>
                );
            })}

            {total > 0 && (
                <span className="text-muted-foreground ms-1 text-xs">
                    {choice('member.community.reactions', total)}
                </span>
            )}
        </div>
    );
}
