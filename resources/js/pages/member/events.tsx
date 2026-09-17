import { Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Ticket } from 'lucide-react';
import { EventDate } from '@/components/public/event-date';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { Registration } from '@/types/event';
import type { Paginated } from '@/types/member';

type Props = {
    registrations: Paginated<Registration>;
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    confirmed: 'default',
    waitlisted: 'secondary',
    cancelled: 'outline',
};

export default function MyEvents({ registrations }: Props) {
    const { t } = useTranslation();

    return (
        <MemberLayout title={t('member.events.title')}>
            <div className="space-y-6">
                <h1 className="text-2xl font-semibold">
                    {t('member.events.title')}
                </h1>

                {registrations.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title={t('common.states.empty')}
                        description={t('member.events.empty')}
                        action={
                            <Button asChild>
                                <Link href="/events">
                                    {t('public.events.title')}
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <div className="space-y-4">
                            {registrations.data.map((registration) => (
                                <RegistrationCard
                                    key={registration.ulid}
                                    registration={registration}
                                />
                            ))}
                        </div>

                        <Pagination meta={registrations.meta} />
                    </>
                )}
            </div>
        </MemberLayout>
    );
}

function RegistrationCard({ registration }: { registration: Registration }) {
    const { t } = useTranslation();

    const event = registration.event;

    return (
        <Card>
            <CardContent className="flex flex-wrap items-start justify-between gap-4 p-5">
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={
                                STATUS_VARIANT[registration.status] ??
                                'secondary'
                            }
                        >
                            {registration.status_label}
                        </Badge>

                        {registration.checkin && (
                            <Badge variant="outline" className="gap-1">
                                <CheckCircle2
                                    className="size-3"
                                    aria-hidden="true"
                                />
                                {t('member.events.checked_in', {
                                    time: formatDateTime(
                                        registration.checkin.checked_in_at,
                                    ),
                                })}
                            </Badge>
                        )}
                    </div>

                    <h2 className="font-semibold">
                        {event?.title ?? registration.registrant_name}
                    </h2>

                    {event && (
                        <EventDate
                            event={event}
                            className="text-muted-foreground text-sm"
                        />
                    )}
                </div>

                <Button asChild size="sm" variant="outline">
                    <Link href={`/my/events/${registration.ulid}`}>
                        <Ticket className="me-1 size-4" aria-hidden="true" />
                        {t('member.events.pass')}
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
