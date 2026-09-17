import { Link } from '@inertiajs/react';
import { CalendarDays, MapPin, Sparkles } from 'lucide-react';
import { EventDate } from '@/components/public/event-date';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import type { PublicEvent } from '@/types/event';
import type { Paginated } from '@/types/member';

type Props = {
    events: Paginated<PublicEvent>;
    flagship?: PublicEvent | null;
};

/**
 * The public event listing.
 *
 * An event whose date is still TBA is listed here — that is the point of the
 * date rule. The Jubilee is announced and open for registration long before
 * the committee fixes a day.
 */
export default function Events({ events, flagship }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.events.title')}
            description={t('public.events.subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.events.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.events.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-5xl space-y-8 px-4 py-10">
                    {flagship && <FlagshipBanner event={flagship} />}

                    {events.data.length === 0 ? (
                        <EmptyState
                            icon={CalendarDays}
                            title={t('common.states.empty')}
                            description={t('public.events.empty')}
                        />
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {events.data.map((event) => (
                                <EventCard key={event.ulid} event={event} />
                            ))}
                        </div>
                    )}

                    <Pagination meta={events.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}

/**
 * The flagship event gets the gold treatment. Gold appears on neither logo, so
 * it is meaningful only where "golden jubilee" gives it meaning.
 */
function FlagshipBanner({ event }: { event: PublicEvent }) {
    const { t } = useTranslation();

    return (
        <Link
            href="/jubilee"
            className="from-brand-green-900 to-brand-green-800 border-brand-gold-500/40 block rounded-xl border bg-gradient-to-r p-6 text-white transition-opacity hover:opacity-95"
        >
            <p className="text-brand-gold-500 flex items-center gap-2 text-sm font-medium">
                <Sparkles className="size-4" aria-hidden="true" />
                {t('jubilee.theme')}
            </p>
            <h2 className="mt-2 text-2xl font-semibold">{event.title}</h2>
            <EventDate event={event} className="mt-3 text-sm text-white/80" />
        </Link>
    );
}

function EventCard({ event }: { event: PublicEvent }) {
    const { t } = useTranslation();

    return (
        <Card className="hover:border-brand-green-600 overflow-hidden transition-colors">
            {event.cover_url && (
                <img
                    src={event.cover_url}
                    alt=""
                    className="h-40 w-full object-cover"
                />
            )}

            <CardContent className="space-y-3 p-5">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{event.type_label}</Badge>
                    {event.is_flagship && (
                        <Badge className="bg-brand-gold-600 text-white">
                            {t('jubilee.title')}
                        </Badge>
                    )}
                </div>

                <h2 className="text-lg font-semibold">
                    <Link
                        href={`/events/${event.slug}`}
                        className="hover:underline"
                    >
                        {event.title}
                    </Link>
                </h2>

                {event.summary && (
                    <p className="text-muted-foreground line-clamp-2 text-sm">
                        {event.summary}
                    </p>
                )}

                <div className="text-muted-foreground space-y-1.5 text-sm">
                    <EventDate event={event} />

                    {event.venue && (
                        <span className="flex items-center gap-2">
                            <MapPin
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{event.venue}</span>
                        </span>
                    )}
                </div>

                <Button asChild size="sm" variant="outline">
                    <Link href={`/events/${event.slug}`}>
                        {t('public.events.details')}
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
