import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, Sparkles } from 'lucide-react';
import { EventDate } from '@/components/public/event-date';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <CalendarDays className="size-3.5" />
                        Reunions & Gatherings
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.events.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.events.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {flagship && <FlagshipBanner event={flagship} />}

                    {events.data.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={CalendarDays}
                                title={t('common.states.empty')}
                                description={t('public.events.empty')}
                            />
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2">
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
            className="group relative block overflow-hidden rounded-3xl border border-amber-500/30 bg-gradient-to-r from-[#0d1b3a] via-[#162a56] to-[#0a1226] p-7 text-white shadow-xl shadow-amber-500/10 transition-all duration-300 hover:border-amber-400/60 hover:shadow-2xl hover:shadow-amber-500/15"
        >
            <div className="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-amber-500/20 blur-2xl" />
            <div className="relative">
                <div className="flex items-center justify-between">
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300 backdrop-blur-md">
                        <Sparkles className="size-3.5" aria-hidden="true" />
                        {t('jubilee.theme')}
                    </span>
                    <span className="flex items-center gap-1 text-xs font-medium text-amber-300/80 transition-transform group-hover:translate-x-1">
                        Explore <ArrowRight className="size-3.5" />
                    </span>
                </div>
                <h2 className="mt-3 text-2xl font-extrabold sm:text-3xl text-white">
                    {event.title}
                </h2>
                <EventDate event={event} className="mt-3 text-sm text-slate-300" />
            </div>
        </Link>
    );
}

function EventCard({ event }: { event: PublicEvent }) {
    const { t } = useTranslation();

    return (
        <div className="group flex flex-col overflow-hidden rounded-3xl border border-teal-500/15 bg-white/90 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-xl">
            {event.cover_url && (
                <div className="relative h-48 w-full overflow-hidden bg-slate-100">
                    <img
                        src={event.cover_url}
                        alt=""
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                </div>
            )}

            <div className="flex flex-1 flex-col justify-between space-y-4 p-6">
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200/60">
                            {event.type_label}
                        </span>
                        {event.is_flagship && (
                            <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 border border-amber-300/70">
                                {t('jubilee.title')}
                            </span>
                        )}
                    </div>

                    <h2 className="text-xl font-bold text-slate-900 group-hover:text-teal-900 transition">
                        <Link
                            href={`/events/${event.slug}`}
                            className="hover:underline"
                        >
                            {event.title}
                        </Link>
                    </h2>

                    {event.summary && (
                        <p className="line-clamp-2 text-sm text-slate-600 leading-relaxed">
                            {event.summary}
                        </p>
                    )}
                </div>

                <div className="space-y-3 border-t border-slate-100 pt-4">
                    <div className="space-y-1.5 text-xs text-slate-500">
                        <EventDate event={event} />

                        {event.venue && (
                            <span className="flex items-center gap-2">
                                <MapPin
                                    className="size-3.5 shrink-0 text-teal-600"
                                    aria-hidden="true"
                                />
                                <span className="truncate">{event.venue}</span>
                            </span>
                        )}
                    </div>

                    <Button
                        asChild
                        size="sm"
                        variant="outline"
                        className="w-full border-teal-500/20 text-teal-700 hover:bg-teal-50 hover:border-teal-500/40 rounded-xl font-medium transition"
                    >
                        <Link href={`/events/${event.slug}`}>
                            {t('public.events.details')}
                            <ArrowRight className="ms-1.5 size-3.5" />
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
