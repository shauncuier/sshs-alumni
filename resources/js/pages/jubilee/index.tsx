import { Deferred, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    Calendar,
    ChevronRight,
    Clock,
    GraduationCap,
    HelpCircle,
    Info,
    MapPin,
    Sparkles,
    Users,
} from 'lucide-react';
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
                <div className="mx-auto max-w-3xl px-4 py-20">
                    <div className="glass-panel-light rounded-3xl p-10 text-center shadow-sm">
                        <EmptyState
                            title={titleEn ?? t('jubilee.title')}
                            description={t('jubilee.no_event')}
                        />
                    </div>
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout
            title={titleEn ?? event.title}
            description={event.summary ?? themeEn ?? undefined}
        >
            {/* ── Hero Section ──────────────────────────────────────────────── */}
            <header className="relative overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white">
                {/* Background Ambient Glow Orbs */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 h-96 w-[42rem] rounded-full bg-gradient-to-tr from-amber-500/15 via-teal-500/15 to-transparent blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/4 -left-20 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute bottom-10 -right-20 h-80 w-80 rounded-full bg-amber-500/15 blur-3xl"
                />

                {/* Subtle Geometric Overlay */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] opacity-[0.04] [background-size:24px_24px]"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-16 text-center sm:py-24">
                    {/* Dual Founding Identity Badges */}
                    <div className="mb-6 flex flex-wrap items-center justify-center gap-3">
                        <div className="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 text-xs font-semibold text-amber-300 shadow-inner backdrop-blur-md sm:text-sm">
                            <GraduationCap className="size-4 text-amber-400" />
                            <span>বিদ্যালয় প্রতিষ্ঠা ১৯৭৬ (৫০ বছর পূর্তি)</span>
                        </div>
                        <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-4 py-1.5 text-xs font-semibold text-teal-300 shadow-inner backdrop-blur-md sm:text-sm">
                            <Users className="size-4 text-teal-400" />
                            <span>আয়োজক: প্রাক্তন ছাত্র-ছাত্রী পরিষদ (প্রতিষ্ঠা ২০১৫)</span>
                        </div>
                    </div>

                    {/* Theme Line */}
                    {themeBn && (
                        <p
                            lang="bn"
                            className="bg-gradient-to-r from-amber-300 via-amber-400 to-amber-200 bg-clip-text text-lg font-bold text-transparent sm:text-2xl"
                        >
                            {themeBn}
                        </p>
                    )}
                    {themeEn && (
                        <p className="mt-1 text-xs font-semibold tracking-widest text-slate-300 uppercase sm:text-sm">
                            {themeEn}
                        </p>
                    )}

                    {/* Main Jubilee Title */}
                    {titleBn && (
                        <h1
                            lang="bn"
                            className="mt-6 bg-gradient-to-r from-amber-200 via-amber-400 to-amber-100 bg-clip-text text-4xl font-extrabold text-transparent drop-shadow-md sm:text-6xl md:text-7xl"
                        >
                            {titleBn}
                        </h1>
                    )}
                    <p className="mt-2 text-xl font-bold tracking-tight text-white/95 sm:text-3xl">
                        {titleEn ?? event.title}
                    </p>

                    {/* School Identity */}
                    <div className="mt-8 space-y-1">
                        {schoolNameBn && (
                            <p lang="bn" className="text-lg font-medium text-slate-200">
                                {schoolNameBn}
                            </p>
                        )}
                        {schoolNameEn && (
                            <p className="text-sm text-slate-400">
                                {schoolNameEn}
                            </p>
                        )}
                    </div>

                    {/* Milestone Ribbon (1976 - 2026) */}
                    {fromYear && toYear && (
                        <div className="mt-8 flex items-center justify-center gap-3 text-sm font-semibold sm:text-base">
                            <span className="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 font-mono text-lg font-bold text-amber-300 shadow-sm backdrop-blur-sm sm:text-xl">
                                {fromYear}
                            </span>
                            <div className="flex items-center gap-2">
                                <span
                                    aria-hidden="true"
                                    className="h-px w-8 bg-gradient-to-r from-amber-400/40 to-amber-400 sm:w-16"
                                />
                                <span className="inline-flex items-center gap-1 rounded-full border border-amber-400/40 bg-amber-400/20 px-3 py-0.5 text-xs font-bold text-amber-200 backdrop-blur-md">
                                    <Sparkles className="size-3 text-amber-300" />
                                    <span>৫০ বছর</span>
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="h-px w-8 bg-gradient-to-l from-amber-400/40 to-amber-400 sm:w-16"
                                />
                            </div>
                            <span className="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 font-mono text-lg font-bold text-amber-300 shadow-sm backdrop-blur-sm sm:text-xl">
                                {toYear}
                            </span>
                        </div>
                    )}

                    {/* Organiser Attribution */}
                    <div className="mt-8">
                        <p className="text-xs tracking-wider text-slate-300 uppercase sm:text-sm">
                            {t('public.events.organised_by', {
                                name: orgNameEn ?? '',
                            })}
                        </p>
                        {orgNameBn && (
                            <p lang="bn" className="mt-0.5 text-sm font-medium text-teal-300 sm:text-base">
                                {orgNameBn}
                            </p>
                        )}
                    </div>

                    {/* THE DATE RULE */}
                    <div className="mt-10">
                        <EventDate
                            event={event}
                            className="glass-panel-dark inline-flex items-center gap-2.5 rounded-full border-amber-400/30 px-6 py-2.5 text-sm font-semibold text-amber-200 shadow-xl"
                        />
                    </div>

                    {/* Countdown Clock (Only rendered when announced) */}
                    <div className="mt-8">
                        <JubileeCountdown
                            event={event}
                            enabled={showCountdown !== false}
                        />
                    </div>

                    {/* Action CTAs */}
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        {registration?.open && (
                            <Button
                                asChild
                                size="lg"
                                className="h-12 rounded-xl bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 px-8 text-base font-bold text-slate-950 shadow-lg shadow-amber-500/25 transition-all duration-300 hover:from-amber-300 hover:to-amber-500 hover:shadow-xl hover:shadow-amber-500/35 hover:-translate-y-0.5"
                            >
                                <Link href={`/events/${event.slug}`} className="flex items-center gap-2">
                                    <span>
                                        {registration.full
                                            ? t('public.events.registration_full')
                                            : t('jubilee.cta.register')}
                                    </span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        )}
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="h-12 rounded-xl border-white/20 bg-white/5 px-6 text-base font-semibold text-white backdrop-blur-md transition-all duration-300 hover:border-white/40 hover:bg-white/15 hover:text-white"
                        >
                            <Link href="/jubilee/schedule" className="flex items-center gap-2">
                                <Calendar className="size-4 text-teal-300" />
                                <span>{t('jubilee.nav.schedule')}</span>
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            {/* ── Main Content Area ────────────────────────────────────────── */}
            <div className="relative min-h-[60vh] bg-gradient-to-b from-[#f0f7f9] via-white to-[#f0f7f9]">
                <div className="relative mx-auto max-w-4xl space-y-12 px-4 py-16">
                    {/* ── About Section ─────────────────────────────────────── */}
                    {event.description && (
                        <section className="glass-panel-light relative overflow-hidden rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                            <div className="mb-4 flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-md shadow-amber-500/20">
                                    <Sparkles className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                                        {t('jubilee.sections.about')}
                                    </h2>
                                    <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                        স্বর্ণালী অধ্যায় ও স্মৃতিচারণ
                                    </p>
                                </div>
                            </div>
                            <p className="mt-4 text-base leading-relaxed text-slate-700 whitespace-pre-line sm:text-lg">
                                {event.description}
                            </p>
                        </section>
                    )}

                    {/* ── Venue Section ─────────────────────────────────────── */}
                    {(event.venue || event.address) && (
                        <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-6 shadow-sm sm:p-8">
                            <div className="flex items-start gap-4">
                                <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white shadow-md shadow-teal-500/20">
                                    <MapPin className="size-6" aria-hidden="true" />
                                </div>
                                <div className="flex-1">
                                    <div className="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
                                        <span>স্থান ও ক্যাম্পাস</span>
                                    </div>
                                    <p className="mt-1 text-lg font-bold text-slate-900 sm:text-xl">
                                        {event.venue ?? t('public.events.venue_tba')}
                                    </p>
                                    {event.address && (
                                        <p className="mt-1 text-sm text-slate-600">
                                            {event.address}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </section>
                    )}

                    {/* ── Timeline Section ──────────────────────────────────── */}
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="mb-6 flex items-center justify-between border-b border-teal-100/60 pb-5">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-600 to-cyan-600 text-white shadow-md shadow-teal-600/20">
                                    <Clock className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                                        {t('jubilee.sections.timeline')}
                                    </h2>
                                    <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                        গৌরবময় ৫০ বছরের ইতিহাস
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Deferred
                            data="milestones"
                            fallback={<TimelineSkeleton />}
                        >
                            <Timeline milestones={milestones ?? []} />
                        </Deferred>
                    </section>

                    {/* ── Sponsors Section ──────────────────────────────────── */}
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="mb-6 flex items-center justify-between border-b border-teal-100/60 pb-5">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-md shadow-amber-500/20">
                                    <Award className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                                        {t('jubilee.sections.sponsors')}
                                    </h2>
                                    <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                        স্পন্সর ও সহযোগী প্রতিষ্ঠান
                                    </p>
                                </div>
                            </div>
                            <Button asChild variant="ghost" size="sm" className="text-teal-700 hover:text-teal-900">
                                <Link href="/jubilee/sponsors" className="flex items-center gap-1 text-xs font-semibold">
                                    <span>{t('jubilee.nav.sponsors')}</span>
                                    <ChevronRight className="size-3.5" />
                                </Link>
                            </Button>
                        </div>

                        <Deferred data="sponsors" fallback={<LogoSkeleton />}>
                            <SponsorWall sponsors={sponsors ?? []} />
                        </Deferred>
                    </section>

                    {/* ── FAQ Section ───────────────────────────────────────── */}
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="mb-6 flex items-center justify-between border-b border-teal-100/60 pb-5">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-600 to-slate-800 text-white shadow-md">
                                    <HelpCircle className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                                        {t('jubilee.sections.faq')}
                                    </h2>
                                    <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                        সাধারণ জিজ্ঞাসা ও উত্তর
                                    </p>
                                </div>
                            </div>
                            <Button asChild variant="ghost" size="sm" className="text-teal-700 hover:text-teal-900">
                                <Link href="/jubilee/faq" className="flex items-center gap-1 text-xs font-semibold">
                                    <span>{t('jubilee.nav.faq')}</span>
                                    <ChevronRight className="size-3.5" />
                                </Link>
                            </Button>
                        </div>

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
            <p className="mt-4 text-sm text-slate-500">
                {t('common.states.empty')}
            </p>
        );
    }

    return (
        <ol className="relative mt-6 space-y-8 border-s-2 border-teal-200/80 ps-8">
            {milestones.map((milestone) => (
                <li
                    key={`${milestone.year}-${milestone.title}`}
                    className="relative group"
                >
                    {/* Glowing Milestone Indicator Node */}
                    <span
                        aria-hidden="true"
                        className={
                            milestone.is_highlighted
                                ? 'absolute -start-[2.55rem] top-1.5 size-5 rounded-full border-4 border-white bg-amber-500 shadow-md shadow-amber-500/30 ring-2 ring-amber-400/50'
                                : 'absolute -start-[2.35rem] top-2 size-3.5 rounded-full border-2 border-white bg-teal-600 shadow-sm'
                        }
                    />

                    <div className="glass-card-hover rounded-2xl border border-teal-500/10 bg-white/70 p-5 shadow-xs transition-all duration-200 hover:border-teal-500/25">
                        <div className="flex flex-wrap items-center gap-2.5">
                            <span
                                className={
                                    milestone.is_highlighted
                                        ? 'rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-3 py-0.5 text-xs font-bold text-slate-950 shadow-xs'
                                        : 'rounded-full bg-teal-50 px-3 py-0.5 text-xs font-semibold text-teal-800'
                                }
                            >
                                {milestone.date_label ?? milestone.year}
                            </span>
                            {milestone.is_highlighted && (
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                    <Sparkles className="size-3 text-amber-500" />
                                    <span>মাইলফলক</span>
                                </span>
                            )}
                        </div>

                        <h3 className="mt-2 text-base font-bold text-slate-900 sm:text-lg">
                            {milestone.title}
                        </h3>

                        {milestone.description && (
                            <p className="mt-1.5 text-sm leading-relaxed text-slate-600">
                                {milestone.description}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}

function SponsorWall({ sponsors }: { sponsors: Sponsor[] }) {
    const { t } = useTranslation();

    if (sponsors.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-teal-200 p-8 text-center">
                <p className="text-sm font-medium text-slate-500">
                    {t('jubilee.sponsors.empty')}
                </p>
            </div>
        );
    }

    return (
        <ul className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
            {sponsors.map((sponsor) => (
                <li
                    key={sponsor.name}
                    className="glass-card-hover flex flex-col items-center justify-center rounded-2xl border border-teal-500/15 bg-white/70 p-6 text-center shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-amber-400/50 hover:shadow-md"
                >
                    {sponsor.logo_url ? (
                        <img
                            src={sponsor.logo_url}
                            alt={sponsor.name}
                            className="h-12 w-auto object-contain transition-transform duration-300 group-hover:scale-105"
                        />
                    ) : (
                        <span className="text-sm font-bold text-slate-800">
                            {sponsor.name}
                        </span>
                    )}
                    {sponsor.tier_label && (
                        <span className="mt-2.5 inline-block rounded-full bg-amber-50 px-2.5 py-0.5 text-[0.65rem] font-semibold tracking-wider text-amber-800 uppercase">
                            {sponsor.tier_label}
                        </span>
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
            <div className="rounded-2xl border border-dashed border-teal-200 p-8 text-center">
                <p className="text-sm font-medium text-slate-500">
                    {t('jubilee.faq.empty')}
                </p>
            </div>
        );
    }

    return (
        <dl className="mt-6 space-y-4">
            {faqs.map((faq) => (
                <div
                    key={faq.question}
                    className="glass-card-hover rounded-2xl border border-teal-500/15 bg-white/80 p-6 shadow-xs transition-all duration-200 hover:border-teal-500/30"
                >
                    <dt className="flex items-start gap-3 text-base font-bold text-slate-900">
                        <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-800">
                            <span className="text-xs font-bold">Q</span>
                        </div>
                        <span>{faq.question}</span>
                    </dt>
                    <dd className="mt-2.5 ps-8 text-sm leading-relaxed text-slate-600">
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
            <Skeleton className="h-6 w-32 rounded-full" />
            <Skeleton className="h-20 w-full rounded-2xl" />
            <Skeleton className="h-20 w-full rounded-2xl" />
        </div>
    );
}

function LogoSkeleton() {
    return (
        <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <Skeleton className="h-24 rounded-2xl" />
            <Skeleton className="h-24 rounded-2xl" />
            <Skeleton className="h-24 rounded-2xl" />
            <Skeleton className="h-24 rounded-2xl" />
        </div>
    );
}
