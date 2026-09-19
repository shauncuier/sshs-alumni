import { useEffect, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import type { PublicEvent } from '@/types/event';

type Props = {
    event: Pick<PublicEvent, 'date_status' | 'starts_at'>;
    /** The `jubilee.show_countdown` setting. Announced AND enabled, or nothing. */
    enabled?: boolean;
};

type Remaining = {
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
};

/**
 * The countdown to the Golden Jubilee.
 *
 * It is NOT RENDERED AT ALL while the date is unannounced — not hidden with
 * CSS, not rendered as zeros. Returning null is the whole contract: there is
 * no DOM node, so there is nothing for a screen reader to read out, nothing in
 * a screenshot, and no way for a styling change to reveal a date that does not
 * exist yet.
 *
 * The same applies once the date has passed: a reunion that already happened
 * does not need a timer counting up.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
export function JubileeCountdown({ event, enabled = true }: Props) {
    const { t } = useTranslation();

    const target =
        enabled && event.date_status === 'announced' && event.starts_at
            ? new Date(event.starts_at).getTime()
            : null;

    const [remaining, setRemaining] = useState<Remaining | null>(() =>
        target === null ? null : remainingUntil(target),
    );

    useEffect(() => {
        if (target === null) {
            setRemaining(null);

            return;
        }

        setRemaining(remainingUntil(target));

        const timer = window.setInterval(() => {
            setRemaining(remainingUntil(target));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [target]);

    if (remaining === null) {
        return null;
    }

    const units: Array<[keyof Remaining, string]> = [
        ['days', t('jubilee.countdown.days')],
        ['hours', t('jubilee.countdown.hours')],
        ['minutes', t('jubilee.countdown.minutes')],
        ['seconds', t('jubilee.countdown.seconds')],
    ];

    return (
        <div className="flex flex-wrap justify-center gap-3 sm:gap-4" role="timer">
            {units.map(([unit, label]) => (
                <div
                    key={unit}
                    className="min-w-20 rounded-2xl border border-amber-400/30 bg-white/5 px-4 py-3.5 text-center shadow-xl shadow-amber-500/10 backdrop-blur-xl transition-all duration-300 hover:border-amber-400/60 hover:bg-white/10 sm:min-w-24"
                >
                    <div className="bg-gradient-to-b from-amber-200 via-amber-400 to-amber-300 bg-clip-text text-3xl font-extrabold tabular-nums text-transparent drop-shadow-sm sm:text-4xl">
                        {formatNumber(remaining[unit])}
                    </div>
                    <div className="mt-1 text-[0.7rem] font-semibold tracking-wider text-amber-200/80 uppercase">
                        {label}
                    </div>
                </div>
            ))}
        </div>
    );
}

/**
 * Null once the moment has passed, so the component unmounts rather than
 * counting upward into the past.
 */
function remainingUntil(target: number): Remaining | null {
    const diff = target - Date.now();

    if (diff <= 0) {
        return null;
    }

    const seconds = Math.floor(diff / 1000);

    return {
        days: Math.floor(seconds / 86400),
        hours: Math.floor((seconds % 86400) / 3600),
        minutes: Math.floor((seconds % 3600) / 60),
        seconds: seconds % 60,
    };
}
