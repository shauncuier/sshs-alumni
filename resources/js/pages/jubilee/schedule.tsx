import { JubileeLayout } from '@/components/public/jubilee-layout';
import { EmptyState } from '@/components/shared/empty-state';
import { useTranslation } from '@/hooks/use-translation';
import type { PublicEvent } from '@/types/event';

type Session = {
    title: string;
    starts_at: string | null;
    description: string | null;
};

type Props = {
    event: PublicEvent | null;
    sessions: Session[];
};

/**
 * The programme.
 *
 * Empty for now, and it says so. The same promise the date rule makes: the
 * page tells you what is not decided rather than pretending it is.
 */
export default function Schedule({ event, sessions }: Props) {
    const { t } = useTranslation();

    return (
        <JubileeLayout
            event={event}
            active="schedule"
            title={t('jubilee.schedule.title')}
        >
            {sessions.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-teal-200/80 p-10 text-center">
                    <EmptyState
                        title={t('jubilee.schedule.title')}
                        description={t('jubilee.schedule.empty')}
                    />
                </div>
            ) : (
                <ol className="space-y-4">
                    {sessions.map((session) => (
                        <li
                            key={session.title}
                            className="glass-card-hover rounded-2xl border border-teal-500/15 bg-white/80 p-6 shadow-xs transition-all duration-200 hover:border-teal-500/30"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <h3 className="text-lg font-bold text-slate-900">
                                    {session.title}
                                </h3>
                                {session.starts_at && (
                                    <span className="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800">
                                        {session.starts_at}
                                    </span>
                                )}
                            </div>
                            {session.description && (
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                    {session.description}
                                </p>
                            )}
                        </li>
                    ))}
                </ol>
            )}
        </JubileeLayout>
    );
}
