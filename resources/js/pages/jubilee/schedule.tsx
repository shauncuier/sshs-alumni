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
                <EmptyState
                    title={t('jubilee.schedule.title')}
                    description={t('jubilee.schedule.empty')}
                />
            ) : (
                <ol className="space-y-4">
                    {sessions.map((session) => (
                        <li
                            key={session.title}
                            className="bg-card rounded-lg border p-5"
                        >
                            <p className="font-medium">{session.title}</p>
                            {session.description && (
                                <p className="text-muted-foreground mt-1 text-sm">
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
