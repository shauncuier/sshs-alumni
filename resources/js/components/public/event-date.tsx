import { CalendarDays } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PublicEvent } from '@/types/event';

type Props = {
    event: Pick<PublicEvent, 'date_status' | 'starts_at' | 'ends_at'>;
    withIcon?: boolean;
    className?: string;
};

/**
 * An event's date, or the line that stands in for one.
 *
 * THE DATE RULE. The committee has not fixed the Golden Jubilee date. Until an
 * administrator publishes it, this renders "Date to be announced" — and it
 * cannot do otherwise by accident, because the server omits `starts_at`
 * entirely while `date_status` is `tba`. There is nothing here to forget.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
export function EventDate({ event, withIcon = true, className }: Props) {
    const { t } = useTranslation();

    const announced =
        event.date_status === 'announced' && Boolean(event.starts_at);

    const label = announced
        ? formatRange(event.starts_at ?? null, event.ends_at ?? null)
        : t('public.events.date_tba');

    return (
        <span
            className={cn('inline-flex items-center gap-2', className)}
            data-date-status={event.date_status}
        >
            {withIcon && (
                <CalendarDays className="size-4 shrink-0" aria-hidden="true" />
            )}
            <span>{label}</span>
        </span>
    );
}

/**
 * A single day renders once; a range that spans days renders both ends.
 */
function formatRange(startsAt: string | null, endsAt: string | null): string {
    const start = formatDate(startsAt);

    if (!endsAt) {
        return start;
    }

    const end = formatDate(endsAt);

    return start === end ? start : `${start} – ${end}`;
}
