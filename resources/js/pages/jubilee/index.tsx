import { Deferred, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { EventDate } from '@/components/public/event-date';
import { JubileeCountdown } from '@/components/public/jubilee-countdown';
import { EmptyState } from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import type { PublicEvent } from '@/types/event';

type Milestone = {
    year: number;
    date_label: string | null;
    title: string;
    description: string | null;
    image_url: string | null;
    is_highlighted: boolean;
};

type Sponsor = {
    name: string;
    website: string | null;
    logo_url: string | null;
    tier: string | null;
    tier_label: string | null;
};

type Props = {
    event: PublicEvent | null;
    registration: { open: boolean; full: boolean } | null;
    milestones?: Milestone[];
    faqs?: Array<{ question: string; answer: string }>;
    sponsors?: Sponsor[];
};

/**
 * The Golden Jubilee landing page.
 *
 * Gold is used HERE and nowhere else on the site — it appears on neither logo,
 * so it only carries meaning on the সুবর্ণজয়ন্তী pages.
 *
 * The fifty years belong to the SCHOOL, founded 1976. The association, founded
 * 2015, is the organiser. The hero says both, separately, because blurring them
 * would misstate the history of two organisations.
 *
 * @see docs/17-golden-jubilee.md
 */
export default function JubileeIndex({
    event,
    registration,
    milestones,
    faqs,
    sponsors,
}: Props) {
    const { t } = useTranslation();

    const titleBn = useSetting<string>('jubilee.title_bn');
    const titleEn = useSetting<string>('jubilee.title_en');
    const themeBn = useSetting<string>('jubilee.theme_bn');
    const themeEn = useSetting<string>('jubilee.theme_en');
    const fromYear = useSetting<number>('jubilee.from_year');
    const toYear = useSetting<number>('jubilee.to_year');
    const showCountdown = useSetting<boolean>('jubilee.show_countdown');
    const schoolNameBn = useSetting<string>('school.name_bn');
    const schoolNameEn = useSetting<string>('school.name_en');
    const orgNameBn = useSetting<string>('organization.name_bn');
    const orgNameEn = useSetting<string>('organization.name_en');

    if (event === null) {
        return (
            <PublicLayout title={titleEn ?? t('jubilee.title')}>
                <div className="mx-auto max-w-3xl px-4 py-16">
                    <EmptyState
                        title={titleEn ?? t('jubilee.title')}
                        description={t('jubilee.no_event')}
                    />
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout
            title={titleEn ?? event.title}
            description={event.summary ?? themeEn ?? undefined}
        >
            {/* ── Hero ──────────────────────────────────────────────── */}
            <header className="from-brand-green-900 to-brand-green-800 bg-gradient-to-b text-white">
                <div className="mx-auto max-w-4xl px-4 py-16 text-center sm:py-24">
                    {themeBn && (
                        <p
                            lang="bn"
                            className="text-brand-gold-500 text-lg font-medium sm:text-xl"
                        >
                            {themeBn}
                        </p>
                    )}
                    {themeEn && (
                        <p className="mt-1 text-sm tracking-wide text-white/60 uppercase">
                            {themeEn}
                        </p>
                    )}

                    {titleBn && (
                        <h1
                            lang="bn"
                            className="text-brand-gold-500 mt-6 text-4xl font-bold sm:text-6xl"
                        >
                            {titleBn}
                        </h1>
                    )}
                    <p className="mt-2 text-xl font-semibold text-white sm:text-2xl">
                        {titleEn ?? event.title}
                    </p>

                    {/* The fifty years are the school's. */}
                    <div className="mt-8 space-y-1">
                        {schoolNameBn && (
                            <p lang="bn" className="text-base text-white/90">
                                {schoolNameBn}
                            </p>
                        )}
                        {schoolNameEn && (
                            <p className="text-sm text-white/60">
                                {schoolNameEn}
                            </p>
                        )}
                    </div>

                    {fromYear && toYear && (
                        <p className="text-brand-gold-500 mt-6 flex items-center justify-center gap-4 text-lg tracking-widest tabular-nums">
                            <span>{fromYear}</span>
                            <span
                                aria-hidden="true"
                                className="bg-brand-gold-500/50 h-px w-16 sm:w-28"
                            />
                            <span>{toYear}</span>
                        </p>
                    )}

                    {/* The association organises; it is not the subject of the
                        anniversary. */}
                    <p className="mt-8 text-sm text-white/70">
                        {t('public.events.organised_by', {
                            name: orgNameEn ?? '',
                        })}
                    </p>
                    {orgNameBn && (
                        <p lang="bn" className="text-sm text-white/90">
                            {orgNameBn}
                        </p>
                    )}

                    {/* THE DATE RULE. */}
                    <div className="mt-10">
                        <EventDate
                            event={event}
                            className="border-brand-gold-500/40 rounded-full border px-5 py-2 text-base text-white"
                        />
                    </div>

                    {/* Not rendered at all while the date is unannounced. */}
                    <div className="mt-8">
                        <JubileeCountdown
                            event={event}
                            enabled={showCountdown !== false}
                        />
                    </div>

                    <div className="mt-10 flex flex-wrap justify-center gap-3">
                        {registration?.open && (
                            <Button asChild size="lg">
                                <Link href={`/events/${event.slug}`}>
                                    {registration.full
                                        ? t('public.events.registration_full')
                                        : t('jubilee.cta.register')}
                                </Link>
                            </Button>
                        )}
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="border-white/30 bg-transparent text-white hover:bg-white/10 hover:text-white"
                        >
                            <Link href="/jubilee/schedule">
                                {t('jubilee.nav.schedule')}
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-4xl space-y-14 px-4 py-14">
                    {/* ── About ─────────────────────────────────────── */}
                    {event.description && (
                        <section>
                            <h2 className="text-brand-green-900 text-2xl font-semibold">
                                {t('jubilee.sections.about')}
                            </h2>
                            <p className="text-muted-foreground mt-4 leading-relaxed whitespace-pre-line">
                                {event.description}
                            </p>
                        </section>
                    )}

                    {/* ── Venue ─────────────────────────────────────── */}
                    {(event.venue || event.address) && (
                        <section className="flex items-start gap-3">
                            <MapPin
                                className="text-brand-green-800 mt-1 size-5 shrink-0"
                                aria-hidden="true"
                            />
                            <div>
                                <p className="font-medium">
                                    {event.venue ??
                                        t('public.events.venue_tba')}
                                </p>
                                {event.address && (
                                    <p className="text-muted-foreground text-sm">
                                        {event.address}
                                    </p>
                                )}
                            </div>
                        </section>
                    )}

                    {/* ── Timeline ──────────────────────────────────── */}
                    <section>
                        <h2 className="text-brand-green-900 text-2xl font-semibold">
                            {t('jubilee.sections.timeline')}
                        </h2>

                        <Deferred
                            data="milestones"
                            fallback={<TimelineSkeleton />}
                        >
                            <Timeline milestones={milestones ?? []} />
                        </Deferred>
                    </section>

                    {/* ── Sponsors ──────────────────────────────────── */}
                    <section>
                        <h2 className="text-brand-green-900 text-2xl font-semibold">
                            {t('jubilee.sections.sponsors')}
                        </h2>

                        <Deferred data="sponsors" fallback={<LogoSkeleton />}>
                            <SponsorWall sponsors={sponsors ?? []} />
                        </Deferred>
                    </section>

                    {/* ── FAQ ───────────────────────────────────────── */}
                    <section>
                        <h2 className="text-brand-green-900 text-2xl font-semibold">
                            {t('jubilee.sections.faq')}
                        </h2>

                        <Deferred data="faqs" fallback={<TimelineSkeleton />}>
                            <Faqs faqs={faqs ?? []} />
                        </Deferred>
                    </section>
                </div>
            </div>
        </PublicLayout>
    );
}

function Timeline({ milestones }: { milestones: Milestone[] }) {
    const { t } = useTranslation();

    if (milestones.length === 0) {
        return (
            <p className="text-muted-foreground mt-4 text-sm">
                {t('common.states.empty')}
            </p>
        );
    }

    return (
        <ol className="border-brand-green-100 mt-6 space-y-6 border-s-2 ps-6">
            {milestones.map((milestone) => (
                <li
                    key={`${milestone.year}-${milestone.title}`}
                    className="relative"
                >
                    <span
                        aria-hidden="true"
                        className={
                            milestone.is_highlighted
                                ? 'bg-brand-gold-600 absolute -start-[1.9rem] mt-1.5 size-3 rounded-full'
                                : 'bg-brand-green-800 absolute -start-[1.9rem] mt-2 size-2 rounded-full'
                        }
                    />
                    <p className="text-brand-green-800 text-sm font-semibold tabular-nums">
                        {milestone.date_label ?? milestone.year}
                    </p>
                    <p className="mt-0.5 font-medium">{milestone.title}</p>
                    {milestone.description && (
                        <p className="text-muted-foreground mt-1 text-sm leading-relaxed">
                            {milestone.description}
                        </p>
                    )}
                </li>
            ))}
        </ol>
    );
}

function SponsorWall({ sponsors }: { sponsors: Sponsor[] }) {
    const { t } = useTranslation();

    if (sponsors.length === 0) {
        return (
            <p className="text-muted-foreground mt-4 text-sm">
                {t('jubilee.sponsors.empty')}
            </p>
        );
    }

    return (
        <ul className="mt-6 flex flex-wrap items-center gap-6">
            {sponsors.map((sponsor) => (
                <li key={sponsor.name} className="text-center">
                    {sponsor.logo_url ? (
                        <img
                            src={sponsor.logo_url}
                            alt={sponsor.name}
                            className="h-12 w-auto object-contain"
                        />
                    ) : (
                        <span className="font-medium">{sponsor.name}</span>
                    )}
                    {sponsor.tier_label && (
                        <p className="text-muted-foreground mt-1 text-xs">
                            {sponsor.tier_label}
                        </p>
                    )}
                </li>
            ))}
        </ul>
    );
}

function Faqs({ faqs }: { faqs: Array<{ question: string; answer: string }> }) {
    const { t } = useTranslation();

    if (faqs.length === 0) {
        return (
            <p className="text-muted-foreground mt-4 text-sm">
                {t('jubilee.faq.empty')}
            </p>
        );
    }

    return (
        <dl className="mt-6 space-y-5">
            {faqs.map((faq) => (
                <div
                    key={faq.question}
                    className="bg-card rounded-lg border p-5"
                >
                    <dt className="font-medium">{faq.question}</dt>
                    <dd className="text-muted-foreground mt-2 text-sm leading-relaxed">
                        {faq.answer}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function TimelineSkeleton() {
    return (
        <div className="mt-6 space-y-4">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-2/3" />
        </div>
    );
}

function LogoSkeleton() {
    return (
        <div className="mt-6 flex gap-6">
            <Skeleton className="h-12 w-28" />
            <Skeleton className="h-12 w-28" />
            <Skeleton className="h-12 w-28" />
        </div>
    );
}
