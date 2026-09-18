import { Deferred, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    GraduationCap,
    Images,
    Megaphone,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { EventDate } from '@/components/public/event-date';
import { JubileeCountdown } from '@/components/public/jubilee-countdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate, formatNumber } from '@/lib/format';
import type {
    AnnouncementCard,
    Milestone,
    NewsCard,
    StoryCard,
} from '@/types/content';
import type { PublicEvent } from '@/types/event';

type Photo = {
    id: number;
    caption: string | null;
    album: string;
    url: string;
    thumb_url: string;
};

type Jubilee = {
    event: PublicEvent;
    title_bn: string | null;
    theme_bn: string | null;
    from_year: number | null;
    to_year: number | null;
    show_countdown: boolean;
    seats_left: number | null;
};

type Props = {
    stats: { members: number; batches: number; events: number; years: number };
    jubilee: Jubilee | null;
    events?: PublicEvent[];
    news?: NewsCard[];
    announcements?: AnnouncementCard[];
    photos?: Photo[];
    stories?: StoryCard[];
    timeline?: Milestone[];
};

/**
 * The front door.
 *
 * EVERY SECTION BELOW THE HERO CAN BE ABSENT. A section with nothing in it
 * does not render at all — an alumni site whose home page shows "Latest news"
 * above an empty box tells a first-time visitor that nobody is looking after
 * it, which on a volunteer-run site is the one impression worth avoiding.
 *
 * Everything under the fold is deferred, so the hero paints before the
 * photographs are counted.
 */
export default function Home({
    stats,
    jubilee,
    events,
    news,
    announcements,
    photos,
    stories,
    timeline,
}: Props) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout title={t('public.hero.organisation')}>
            {/* ── Hero ───────────────────────────────────────────────── */}
            <section className="from-brand-green-900 to-brand-green-800 bg-gradient-to-br text-white">
                <div className="mx-auto max-w-5xl px-4 py-16 text-center sm:py-20">
                    {jubilee && (
                        <Badge className="bg-brand-gold-500 text-brand-green-900 hover:bg-brand-gold-500 mb-5">
                            {t('public.home.jubilee_badge')}
                        </Badge>
                    )}

                    {/* The association's own name, in its own script. Set
                        phrases, not copy to be translated. */}
                    {jubilee?.title_bn && (
                        <p
                            className="font-bangla text-brand-gold-500 text-2xl"
                            lang="bn"
                        >
                            {jubilee.title_bn}
                        </p>
                    )}

                    <h1 className="mt-3 text-3xl font-semibold sm:text-5xl">
                        {t('public.hero.school')}
                    </h1>

                    <p className="mt-3 text-lg text-white/80">
                        {t('public.hero.organisation')}
                    </p>

                    {jubilee?.theme_bn && (
                        <p
                            className="font-bangla mt-4 text-lg text-white/70"
                            lang="bn"
                        >
                            {jubilee.theme_bn}
                        </p>
                    )}

                    <p className="text-brand-gold-500 mt-5 text-xl font-medium tracking-widest">
                        {t('public.hero.milestone')}
                    </p>

                    {jubilee && (
                        <div className="mt-8">
                            {/* Renders nothing at all until the committee
                                announces a date. */}
                            <JubileeCountdown
                                event={jubilee.event}
                                enabled={jubilee.show_countdown}
                            />

                            <p className="mt-4 text-white/70">
                                <EventDate event={jubilee.event} />
                            </p>
                        </div>
                    )}

                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                        <Button
                            size="lg"
                            className="bg-brand-gold-500 text-brand-green-900 hover:bg-brand-gold-600"
                            asChild
                        >
                            <Link href="/join">
                                {t('public.hero.cta_primary')}
                            </Link>
                        </Button>

                        {jubilee && (
                            <Button
                                size="lg"
                                variant="outline"
                                className="border-white/40 bg-transparent text-white hover:bg-white/10 hover:text-white"
                                asChild
                            >
                                <Link href="/jubilee">
                                    {t('public.hero.cta_secondary')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
            </section>

            {/* ── Counts ─────────────────────────────────────────────── */}
            <section className="bg-brand-cream border-b">
                <div className="mx-auto grid max-w-5xl grid-cols-2 gap-4 px-4 py-10 sm:grid-cols-4">
                    <Stat
                        icon={Users}
                        value={stats.members}
                        label={t('public.stats.members')}
                    />
                    <Stat
                        icon={GraduationCap}
                        value={stats.batches}
                        label={t('public.stats.batches')}
                    />
                    <Stat
                        icon={CalendarDays}
                        value={stats.events}
                        label={t('public.stats.events')}
                    />
                    <Stat
                        icon={Images}
                        value={stats.years}
                        label={t('public.stats.years')}
                    />
                </div>
            </section>

            <div className="bg-background">
                <div className="mx-auto max-w-5xl space-y-12 px-4 py-12">
                    {/* ── Announcements ─────────────────────────────── */}
                    <Deferred data="announcements" fallback={<RowSkeleton />}>
                        <AnnouncementStrip items={announcements ?? []} />
                    </Deferred>

                    {/* ── Events ────────────────────────────────────── */}
                    <Deferred data="events" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.upcoming_events')}
                            href="/events"
                            linkLabel={t('public.home.all_events')}
                            empty={(events ?? []).length === 0}
                        >
                            <div className="grid gap-4 sm:grid-cols-3">
                                {(events ?? []).map((event) => (
                                    <Card key={event.ulid}>
                                        <CardContent className="space-y-2 pt-6">
                                            <Badge variant="outline">
                                                {event.type_label}
                                            </Badge>
                                            <Link
                                                href={`/events/${event.slug}`}
                                                className="block font-medium hover:underline"
                                            >
                                                {event.title}
                                            </Link>
                                            <p className="text-muted-foreground text-sm">
                                                <EventDate event={event} />
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── News ──────────────────────────────────────── */}
                    <Deferred data="news" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.latest_news')}
                            href="/news"
                            linkLabel={t('public.home.read_all_news')}
                            empty={(news ?? []).length === 0}
                        >
                            <div className="grid gap-4 sm:grid-cols-3">
                                {(news ?? []).map((item) => (
                                    <Card
                                        key={item.slug}
                                        className="overflow-hidden"
                                    >
                                        {item.cover_url && (
                                            <img
                                                src={item.cover_url}
                                                alt=""
                                                loading="lazy"
                                                className="h-36 w-full object-cover"
                                            />
                                        )}
                                        <CardContent className="space-y-2 pt-5">
                                            <Link
                                                href={item.url}
                                                className="block font-medium hover:underline"
                                            >
                                                {item.title}
                                            </Link>
                                            {item.excerpt && (
                                                <p className="text-muted-foreground text-sm">
                                                    {item.excerpt}
                                                </p>
                                            )}
                                            <p className="text-muted-foreground text-xs">
                                                {formatDate(item.published_at)}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── Photos ────────────────────────────────────── */}
                    <Deferred data="photos" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.recent_photos')}
                            href="/gallery"
                            linkLabel={t('public.home.all_photos')}
                            empty={(photos ?? []).length === 0}
                        >
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                {(photos ?? []).map((photo) => (
                                    <Link
                                        key={photo.id}
                                        href={photo.url}
                                        className="overflow-hidden rounded-md border"
                                    >
                                        <img
                                            src={photo.thumb_url}
                                            alt={photo.caption ?? photo.album}
                                            loading="lazy"
                                            className="h-32 w-full object-cover transition-transform hover:scale-105"
                                        />
                                    </Link>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── Stories ───────────────────────────────────── */}
                    <Deferred data="stories" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.featured_alumni')}
                            href="/stories"
                            linkLabel={t('public.home.all_stories')}
                            empty={(stories ?? []).length === 0}
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                {(stories ?? []).map((story) => (
                                    <Card key={story.slug}>
                                        <CardContent className="flex gap-4 pt-6">
                                            {story.photo_url && (
                                                <img
                                                    src={story.photo_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="size-16 shrink-0 rounded-full object-cover"
                                                />
                                            )}
                                            <div className="min-w-0 space-y-1">
                                                <Link
                                                    href={story.url}
                                                    className="block font-medium hover:underline"
                                                >
                                                    {story.title}
                                                </Link>
                                                <p className="text-muted-foreground text-xs">
                                                    {t('public.stories.by', {
                                                        name: story.author_name,
                                                    })}
                                                    {story.career_summary
                                                        ? ` · ${story.career_summary}`
                                                        : ''}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    {story.excerpt}
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── Timeline ──────────────────────────────────── */}
                    <Deferred data="timeline" fallback={<RowSkeleton />}>
                        <Section
                            title={t('public.sections.timeline')}
                            href="/about/school"
                            linkLabel={t('public.home.full_timeline')}
                            empty={(timeline ?? []).length === 0}
                        >
                            <ol className="grid gap-4 sm:grid-cols-4">
                                {(timeline ?? []).map((milestone) => (
                                    <li
                                        key={milestone.year}
                                        className="border-brand-gold-500 border-s-2 ps-4"
                                    >
                                        <p className="text-brand-green-900 text-lg font-semibold">
                                            {milestone.year}
                                        </p>
                                        <p className="text-sm font-medium">
                                            {milestone.title}
                                        </p>
                                        {milestone.description && (
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {milestone.description}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </Section>
                    </Deferred>
                </div>
            </div>

            {/* ── Join ───────────────────────────────────────────────── */}
            <section className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-12 text-center">
                    <h2 className="text-2xl font-semibold">
                        {t('public.sections.join_cta')}
                    </h2>
                    <p className="mt-2 text-white/70">
                        {t('public.home.join_body')}
                    </p>

                    {jubilee?.seats_left != null && (
                        <p className="text-brand-gold-500 mt-3 text-sm">
                            {choice(
                                'public.home.seats_left',
                                jubilee.seats_left,
                            )}
                        </p>
                    )}

                    <Button
                        size="lg"
                        className="bg-brand-gold-500 text-brand-green-900 hover:bg-brand-gold-600 mt-6"
                        asChild
                    >
                        <Link href="/join">{t('public.hero.cta_primary')}</Link>
                    </Button>
                </div>
            </section>
        </PublicLayout>
    );
}

function Stat({
    icon: Icon,
    value,
    label,
}: {
    icon: typeof Users;
    value: number;
    label: string;
}) {
    return (
        <div className="text-center">
            <Icon
                className="text-brand-green-800 mx-auto size-5"
                aria-hidden="true"
            />
            <p className="text-brand-green-900 mt-2 text-3xl font-semibold tabular-nums">
                {formatNumber(value)}
            </p>
            <p className="text-muted-foreground text-sm">{label}</p>
        </div>
    );
}

/**
 * A section that renders nothing when it has nothing.
 */
function Section({
    title,
    href,
    linkLabel,
    empty,
    children,
}: {
    title: string;
    href: string;
    linkLabel: string;
    empty: boolean;
    children: ReactNode;
}) {
    if (empty) {
        return null;
    }

    return (
        <section>
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-brand-green-900 text-xl font-semibold">
                    {title}
                </h2>
                <Link
                    href={href}
                    className="text-brand-green-800 inline-flex items-center gap-1 text-sm hover:underline"
                >
                    {linkLabel}
                    <ArrowRight
                        className="size-3.5 rtl:rotate-180"
                        aria-hidden="true"
                    />
                </Link>
            </div>

            {children}
        </section>
    );
}

function AnnouncementStrip({ items }: { items: AnnouncementCard[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="space-y-2">
            {items.map((announcement) => (
                <div
                    key={announcement.id}
                    className="bg-brand-cream flex items-start gap-3 rounded-md border px-4 py-3"
                >
                    <Megaphone
                        className="text-brand-green-800 mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <div className="min-w-0">
                        <p className="text-sm font-medium">
                            {announcement.title}
                        </p>
                        <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                            {announcement.body}
                        </p>
                    </div>
                    {announcement.level !== 'info' && (
                        <Badge
                            variant={
                                announcement.level === 'urgent'
                                    ? 'destructive'
                                    : 'secondary'
                            }
                            className="ms-auto shrink-0"
                        >
                            {t(
                                `enums.announcement_level.${announcement.level}`,
                            )}
                        </Badge>
                    )}
                </div>
            ))}
        </section>
    );
}

function CardsSkeleton() {
    return (
        <div className="grid gap-4 sm:grid-cols-3">
            {[0, 1, 2].map((key) => (
                <Skeleton key={key} className="h-40 w-full" />
            ))}
        </div>
    );
}

function RowSkeleton() {
    return <Skeleton className="h-16 w-full" />;
}
