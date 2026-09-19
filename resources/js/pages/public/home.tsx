import { Deferred, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpen,
    CalendarDays,
    ChevronRight,
    GraduationCap,
    HeartHandshake,
    Images,
    MapPin,
    Megaphone,
    Sparkles,
    Users,
} from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { EventDate } from '@/components/public/event-date';
import { JubileeCountdown } from '@/components/public/jubilee-countdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
 * Modern Glassmorphic Alumni Home Page.
 *
 * Utilizes multi-layered frosted glass panels, ambient gradient lighting,
 * and high-contrast typography to produce a sleek, prestigious, and modern
 * academic institution portal.
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
            {/* ── 1. Hero Section with Ambient Glassmorphism ─────────────── */}
            <section className="relative overflow-hidden bg-gradient-to-b from-[#060b18] via-[#091329] to-[#0c1836] text-white">
                {/* Radiant ambient glow spheres behind frosted glass */}
                <div
                    className="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 size-[650px] rounded-full opacity-35 blur-[120px]"
                    style={{
                        background:
                            'radial-gradient(circle, rgba(6, 182, 212, 0.7) 0%, rgba(14, 116, 144, 0.4) 50%, transparent 80%)',
                    }}
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute top-1/3 -left-20 size-[500px] rounded-full opacity-25 blur-[100px]"
                    style={{
                        background:
                            'radial-gradient(circle, rgba(79, 70, 229, 0.6) 0%, transparent 70%)',
                    }}
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute top-1/4 -right-20 size-[500px] rounded-full opacity-20 blur-[100px]"
                    style={{
                        background:
                            'radial-gradient(circle, rgba(245, 158, 11, 0.5) 0%, transparent 70%)',
                    }}
                    aria-hidden="true"
                />

                {/* Subtle geometric dot grid pattern */}
                <div
                    className="pointer-events-none absolute inset-0 opacity-[0.035]"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle, #ffffff 1px, transparent 1px)',
                        backgroundSize: '24px 24px',
                    }}
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 pt-12 pb-24 sm:pt-16 sm:pb-32 text-center">
                    {/* Dual Institution Crest Pill (Glassmorphic) */}
                    <div className="glass-pill inline-flex flex-wrap items-center justify-center gap-3 sm:gap-6 rounded-full px-5 py-2.5 text-xs sm:text-sm text-white/95 shadow-xl">
                        <div className="flex items-center gap-2">
                            <img
                                src="/brand/logo-school.png"
                                alt="Sabuj Shikshayatan Govt. High School Logo"
                                className="size-6 object-contain drop-shadow-sm"
                            />
                            <span className="font-semibold text-cyan-300">
                                বিদ্যালয় • স্থাপিত ১৯৭৬
                            </span>
                        </div>
                        <span className="hidden text-white/35 sm:inline">•</span>
                        <div className="flex items-center gap-2">
                            <img
                                src="/brand/logo-association.png"
                                alt="Former Students Association Logo"
                                className="size-6 object-contain drop-shadow-sm"
                            />
                            <span className="font-semibold text-indigo-300">
                                পরিষদ • স্থাপিত ২০১৫
                            </span>
                        </div>
                    </div>

                    {/* Golden Jubilee Commemorative Glass Pill */}
                    {jubilee && (
                        <div className="mt-5 flex justify-center">
                            <div className="glass-pill inline-flex items-center gap-2 rounded-full border-amber-500/40 bg-amber-500/10 px-4 py-1.5 text-xs sm:text-sm font-semibold text-amber-400 shadow-md">
                                <Sparkles className="size-3.5 text-amber-400 animate-pulse" />
                                <span>
                                    {jubilee.title_bn ??
                                        t('public.home.jubilee_badge')}
                                </span>
                                <span className="text-amber-400/50">•</span>
                                <span>Golden Jubilee 2026</span>
                            </div>
                        </div>
                    )}

                    {/* Main School Headline in Authentic Bangla */}
                    <h1
                        lang="bn"
                        className="font-bangla mt-6 text-3xl font-bold tracking-tight text-white drop-shadow-[0_2px_12px_rgba(0,0,0,0.5)] sm:text-5xl lg:text-6xl"
                    >
                        {t('public.hero.school')}
                    </h1>

                    {/* English Subtitle */}
                    <p className="mt-2 text-base font-medium tracking-wide text-cyan-100/90 sm:text-xl">
                        Sabuj Shikshayatan Government High School
                    </p>

                    {/* Association Organiser Title */}
                    <p
                        lang="bn"
                        className="font-bangla mt-3 text-lg font-semibold text-white/90 sm:text-2xl"
                    >
                        {t('public.hero.organisation')}
                    </p>

                    {/* Jubilee Theme Line */}
                    {jubilee?.theme_bn && (
                        <p
                            lang="bn"
                            className="font-bangla mt-2 text-base sm:text-lg font-semibold text-amber-400"
                        >
                            {jubilee.theme_bn}
                        </p>
                    )}

                    {/* Milestone Ribbon */}
                    <div className="glass-pill mt-4 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs sm:text-sm font-semibold tracking-widest text-amber-400/95 uppercase shadow-inner">
                        <span>{t('public.hero.milestone')}</span>
                        <span className="inline-block size-1.5 rounded-full bg-amber-400"></span>
                        <span>50 Years of Academic Excellence</span>
                    </div>

                    {/* Official Verified Institutional Facts Strip */}
                    <div className="mt-5 flex flex-wrap items-center justify-center gap-2 sm:gap-3 text-xs text-white/90">
                        <span className="glass-pill flex items-center gap-1.5 rounded-full px-3.5 py-1.5 shadow-sm">
                            <MapPin className="size-3.5 text-cyan-400" />
                            <span>হাফিজ জুট মিলস, বার আউলিয়া, সীতাকুণ্ড, চট্টগ্রাম</span>
                        </span>
                        <span className="glass-pill flex items-center gap-1.5 rounded-full px-3.5 py-1.5 shadow-sm">
                            <Award className="size-3.5 text-amber-400" />
                            <span>EIIN: 105070 • চট্টগ্রাম শিক্ষা বোর্ড</span>
                        </span>
                        <span className="glass-pill flex items-center gap-1.5 rounded-full px-3.5 py-1.5 shadow-sm">
                            <BookOpen className="size-3.5 text-indigo-400" />
                            <span>বিজ্ঞান • ব্যবসায় শিক্ষা • মানবিক</span>
                        </span>
                        <span className="glass-pill flex items-center gap-1.5 rounded-full px-3.5 py-1.5 shadow-sm">
                            <Sparkles className="size-3.5 text-teal-300" />
                            <span>জ্ঞানই শক্তি (Knowledge is Power)</span>
                        </span>
                    </div>

                    {/* High-Impact Glassmorphic Action Buttons */}
                    <div className="mt-9 flex flex-wrap items-center justify-center gap-4">
                        <Button
                            size="lg"
                            className="group h-12 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-8 text-base font-bold text-slate-950 shadow-[0_10px_25px_-5px_rgba(245,158,11,0.4)] transition-all hover:scale-[1.03] hover:from-amber-400 hover:to-amber-500 hover:shadow-[0_15px_30px_-5px_rgba(245,158,11,0.5)]"
                            asChild
                        >
                            <Link href="/join">
                                {t('public.hero.cta_primary')}
                                <ArrowRight className="ms-2 size-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </Button>

                        {jubilee ? (
                            <Button
                                size="lg"
                                variant="outline"
                                className="glass-pill h-12 rounded-xl border-white/25 px-6 text-base font-medium text-white transition-all hover:scale-[1.02] hover:bg-white/20 hover:text-white"
                                asChild
                            >
                                <Link href="/jubilee">
                                    {t('public.hero.cta_secondary')}
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                size="lg"
                                variant="outline"
                                className="glass-pill h-12 rounded-xl border-white/25 px-6 text-base font-medium text-white transition-all hover:scale-[1.02] hover:bg-white/20 hover:text-white"
                                asChild
                            >
                                <Link href="/batches">
                                    {t('public.home.members_cta')}
                                </Link>
                            </Button>
                        )}

                        <Button
                            size="lg"
                            variant="ghost"
                            className="h-12 rounded-xl text-white/80 transition-all hover:bg-white/10 hover:text-white"
                            asChild
                        >
                            <Link href="/login">
                                {t('common.actions.login')}
                            </Link>
                        </Button>
                    </div>

                    {/* Quick Cohort Explorer Glass Pill */}
                    <div className="mt-10 flex items-center justify-center">
                        <Link
                            href="/batches"
                            className="glass-pill inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs text-white/90 transition-all hover:scale-[1.02] hover:bg-white/20 hover:text-white"
                        >
                            <GraduationCap className="size-4 text-cyan-400" />
                            <span>Find your cohort: SSC 1981 to Present</span>
                            <ChevronRight className="size-3.5 opacity-75" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* ── 2. Floating Glassmorphic Metric Command Slab ───────────────────── */}
            <section className="relative z-20 mx-auto -mt-12 sm:-mt-16 max-w-6xl px-4">
                {/* Ambient Soft Glow underneath the slab */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -inset-1 rounded-[2.5rem] bg-gradient-to-r from-teal-500/20 via-cyan-500/20 to-amber-500/15 blur-xl opacity-75"
                />

                <div className="relative overflow-hidden rounded-3xl border border-teal-500/25 bg-[#080e22]/90 p-5 sm:p-7 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.6)] backdrop-blur-2xl">
                    {/* Top Luminous Gradient Edge */}
                    <div
                        aria-hidden="true"
                        className="absolute top-0 left-0 h-[2px] w-full bg-gradient-to-r from-teal-500/40 via-cyan-400 to-amber-400/50"
                    />

                    <div className="grid grid-cols-2 gap-3.5 sm:gap-6 lg:grid-cols-4">
                        <StatCard
                            icon={Users}
                            iconBg="bg-gradient-to-br from-cyan-500/20 to-teal-500/20 text-cyan-300 border border-cyan-400/30"
                            glowColor="from-cyan-500/10 to-transparent"
                            value={stats.members}
                            label={t('public.stats.members')}
                            sublabel="Verified alumni members worldwide"
                        />
                        <StatCard
                            icon={GraduationCap}
                            iconBg="bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 text-indigo-300 border border-indigo-400/30"
                            glowColor="from-indigo-500/10 to-transparent"
                            value={stats.batches}
                            label={t('public.stats.batches')}
                            sublabel="SSC cohorts with active members"
                        />
                        <StatCard
                            icon={CalendarDays}
                            iconBg="bg-gradient-to-br from-amber-500/20 to-orange-500/20 text-amber-300 border border-amber-400/30"
                            glowColor="from-amber-500/10 to-transparent"
                            value={stats.events}
                            label={t('public.stats.events')}
                            sublabel="Reunions & academic gatherings"
                        />
                        <StatCard
                            icon={Award}
                            iconBg="bg-gradient-to-br from-teal-500/20 to-emerald-500/20 text-teal-300 border border-teal-400/30"
                            glowColor="from-teal-500/10 to-transparent"
                            value={stats.years}
                            label={t('public.stats.years')}
                            sublabel="Years of school heritage (1976–2026)"
                        />
                    </div>
                </div>
            </section>

            {/* Main Content Sections with Ambient Glows */}
            <div className="relative overflow-hidden bg-background">
                {/* Ambient backdrop glows for translucent glass cards */}
                <div
                    className="pointer-events-none absolute -top-40 right-0 size-[600px] rounded-full bg-cyan-200/25 blur-[140px]"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute top-1/3 -left-40 size-[500px] rounded-full bg-indigo-200/20 blur-[130px]"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute bottom-40 right-10 size-[600px] rounded-full bg-teal-200/20 blur-[140px]"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl space-y-16 px-4 py-12 sm:py-16">
                    {/* ── 3. Live Announcements ─────────────────────────────── */}
                    <Deferred data="announcements" fallback={<RowSkeleton />}>
                        <AnnouncementStrip items={announcements ?? []} />
                    </Deferred>

                    {/* ── 4. Golden Jubilee 2026 Glassmorphic Showcase ───────── */}
                    {jubilee && (
                        <section className="glass-panel-dark relative overflow-hidden rounded-3xl border border-amber-500/35 p-6 sm:p-12 text-white shadow-2xl">
                            {/* Ambient Jubilee backlight */}
                            <div
                                className="pointer-events-none absolute inset-0 opacity-20 mix-blend-overlay"
                                style={{
                                    backgroundImage:
                                        'url(/brand/jubilee-banner.jpg)',
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                }}
                            />
                            <div
                                className="pointer-events-none absolute -top-20 left-1/2 -translate-x-1/2 size-[450px] rounded-full bg-amber-500/20 blur-[90px]"
                                aria-hidden="true"
                            />

                            <div className="relative z-10 flex flex-col items-center text-center">
                                <Badge className="glass-pill border-amber-500/40 bg-amber-500/20 px-3.5 py-1 text-xs font-bold text-amber-300 shadow-md">
                                    {t('public.home.jubilee_badge')} • 1976 — 2026
                                </Badge>

                                {jubilee.title_bn && (
                                    <h2
                                        lang="bn"
                                        className="font-bangla mt-4 text-2xl sm:text-4xl font-bold text-amber-400 drop-shadow-md"
                                    >
                                        {jubilee.title_bn}
                                    </h2>
                                )}

                                <p className="mt-2 text-base sm:text-xl font-medium text-white/95">
                                    The Grand 50th Anniversary Celebration of Sabuj Shikshayatan Govt. High School
                                </p>

                                {jubilee.theme_bn && (
                                    <p
                                        lang="bn"
                                        className="font-bangla mt-2 text-base sm:text-lg font-semibold text-cyan-300"
                                    >
                                        {jubilee.theme_bn}
                                    </p>
                                )}

                                {/* Jubilee Live Countdown in Glass Blocks */}
                                <div className="mt-8 w-full max-w-xl">
                                    <JubileeCountdown
                                        event={jubilee.event}
                                        enabled={jubilee.show_countdown}
                                    />

                                    <div className="mt-5 flex flex-wrap items-center justify-center gap-4 text-sm text-white/85">
                                        <span className="flex items-center gap-1.5 font-medium">
                                            <EventDate event={jubilee.event} />
                                        </span>

                                        {jubilee.seats_left != null && (
                                            <Badge
                                                variant="outline"
                                                className="glass-pill border-amber-500/50 bg-amber-500/15 text-amber-300 font-semibold"
                                            >
                                                {choice(
                                                    'public.home.seats_left',
                                                    jubilee.seats_left,
                                                )}
                                            </Badge>
                                        )}
                                    </div>
                                </div>

                                {/* Jubilee Action Strip */}
                                <div className="mt-8 flex flex-wrap justify-center gap-3.5">
                                    <Button
                                        className="bg-gradient-to-r from-amber-500 to-amber-600 font-bold text-slate-950 shadow-lg hover:from-amber-400 hover:to-amber-500 hover:scale-[1.02] transition-all"
                                        asChild
                                    >
                                        <Link
                                            href={`/events/${jubilee.event.slug}`}
                                        >
                                            {t('public.events.register')}
                                        </Link>
                                    </Button>

                                    <Button
                                        variant="outline"
                                        className="glass-pill border-white/30 text-white hover:bg-white/20 hover:text-white"
                                        asChild
                                    >
                                        <Link href="/jubilee">
                                            {t('public.hero.cta_secondary')}
                                        </Link>
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        className="text-white/80 hover:bg-white/10 hover:text-white"
                                        asChild
                                    >
                                        <Link href="/jubilee/schedule">
                                            Program Schedule
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </section>
                    )}

                    {/* ── 5. Pillars of the Association (Glass Grid) ─────────── */}
                    <section className="glass-panel-light rounded-3xl p-6 sm:p-10 shadow-lg">
                        <div className="text-center">
                            <Badge
                                variant="outline"
                                className="glass-pill-light border-cyan-500/30 text-cyan-800 font-semibold"
                            >
                                Community & Legacy
                            </Badge>
                            <h2 className="mt-3 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                                Connecting Generations of SSHS Alumni
                            </h2>
                            <p className="mx-auto mt-2 max-w-2xl text-sm sm:text-base text-slate-600">
                                From the pioneer SSC batch of 1981 to fresh graduates, our association keeps former students connected with one another and with the school.
                            </p>
                        </div>

                        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <PillarCard
                                icon={Users}
                                iconBg="bg-gradient-to-br from-cyan-500/15 to-teal-500/15 text-cyan-700 border border-cyan-500/25"
                                title="Global Alumni Directory"
                                description="সারা বিশ্বে প্রতিষ্ঠিত বিজ্ঞান, ব্যবসায় শিক্ষা ও মানবিক শাখার প্রাক্তন শিক্ষক ও শিক্ষার্থীদের সঙ্গে সরাসরি সংযোগ ও সার্বক্ষণিক যোগাযোগ।"
                                link="/batches"
                                linkText="Explore Batches"
                            />
                            <PillarCard
                                icon={CalendarDays}
                                iconBg="bg-gradient-to-br from-indigo-500/15 to-purple-500/15 text-indigo-700 border border-indigo-500/25"
                                title="Sports Festival & Galas"
                                description="বার আউলিয়া হাফিজ জুট মিলস ফুটবল মাঠে ঐতিহ্যবাহী বার্ষিক স্পোর্টস ফেস্টিভ্যাল, পুনর্মিলনী ও সুবর্ণজয়ন্তী ২০২৬ উদযাপন।"
                                link="/events"
                                linkText="View Events"
                            />
                            <PillarCard
                                icon={HeartHandshake}
                                iconBg="bg-gradient-to-br from-amber-500/15 to-orange-500/15 text-amber-700 border border-amber-500/25"
                                title="Scholarships & Welfare"
                                description="মেধাবী ও অসচ্ছল শিক্ষার্থীদের জন্য শিক্ষা বৃত্তি, ছাত্র কল্যাণ তহবিল এবং তরুণ গ্র্যাজুয়েটদের ক্যারিয়ার কাউন্সেলিং ও দিকনির্দেশনা।"
                                link="/about"
                                linkText="Our Mission"
                            />
                            <PillarCard
                                icon={BookOpen}
                                iconBg="bg-gradient-to-br from-teal-500/15 to-emerald-500/15 text-teal-700 border border-teal-500/25"
                                title="Giving Back to Alma Mater"
                                description="বিদ্যালয়ের ঐতিহ্য সংরক্ষণ, আধুনিক লাইব্রেরি ও বিজ্ঞানাগার উন্নয়ন এবং ক্যাম্পাস অবকাঠামো সমৃদ্ধকরণে সক্রিয় অবদান।"
                                link="/donate"
                                linkText="How to Give"
                            />
                        </div>
                    </section>

                    {/* ── 6. Upcoming Events (Glassmorphic Cards) ─────────────── */}
                    <Deferred data="events" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.upcoming_events')}
                            href="/events"
                            linkLabel={t('public.home.all_events')}
                            empty={(events ?? []).length === 0}
                        >
                            <div className="grid gap-5 sm:grid-cols-3">
                                {(events ?? []).map((event) => (
                                    <div
                                        key={event.ulid}
                                        className="glass-panel-light glass-card-hover group flex flex-col justify-between rounded-2xl overflow-hidden shadow-sm"
                                    >
                                        <div className="space-y-3 p-6">
                                            <div className="flex items-center justify-between gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className="glass-pill-light text-xs font-semibold text-cyan-800"
                                                >
                                                    {event.type_label}
                                                </Badge>
                                                {event.organizer_name && (
                                                    <span className="text-[0.75rem] font-medium text-slate-500 truncate max-w-[120px]">
                                                        {event.organizer_name}
                                                    </span>
                                                )}
                                            </div>

                                            <Link
                                                href={`/events/${event.slug}`}
                                                className="block text-base font-bold text-slate-900 transition-colors group-hover:text-cyan-700"
                                            >
                                                {event.title}
                                            </Link>

                                            <div className="space-y-1.5 text-xs text-slate-600">
                                                <p className="flex items-center gap-1.5">
                                                    <EventDate event={event} />
                                                </p>
                                                {event.venue && (
                                                    <p className="flex items-center gap-1.5">
                                                        <MapPin className="size-3.5 shrink-0 text-cyan-600" />
                                                        <span className="truncate">
                                                            {event.venue}
                                                        </span>
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between border-t border-slate-200/60 bg-white/40 px-6 py-3.5">
                                            <span className="text-xs font-bold text-cyan-700">
                                                {event.registration_fee
                                                    ? `${event.currency} ${event.registration_fee}`
                                                    : 'Free'}
                                            </span>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-8 text-xs font-semibold text-cyan-800 group-hover:text-cyan-600"
                                                asChild
                                            >
                                                <Link
                                                    href={`/events/${event.slug}`}
                                                >
                                                    {t('public.events.details')}
                                                    <ArrowRight className="ms-1 size-3" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── 7. Latest News & Dispatches (Glassmorphic Magazine) ── */}
                    <Deferred data="news" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.latest_news')}
                            href="/news"
                            linkLabel={t('public.home.read_all_news')}
                            empty={(news ?? []).length === 0}
                        >
                            <div className="grid gap-5 lg:grid-cols-3">
                                {news && news.length > 0 && (
                                    <div className="glass-panel-light glass-card-hover group flex flex-col justify-between rounded-3xl overflow-hidden shadow-sm lg:col-span-2">
                                        {news[0].cover_url && (
                                            <div className="aspect-video w-full overflow-hidden bg-slate-100">
                                                <img
                                                    src={news[0].cover_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            </div>
                                        )}
                                        <div className="flex flex-1 flex-col justify-between space-y-3 p-6 sm:p-8">
                                            <div className="space-y-2.5">
                                                <div className="flex items-center gap-2 text-xs text-slate-500">
                                                    <Badge
                                                        variant="secondary"
                                                        className="glass-pill-light text-[0.7rem] uppercase font-bold tracking-wider text-cyan-800"
                                                    >
                                                        Latest Bulletin
                                                    </Badge>
                                                    <span>•</span>
                                                    <time>
                                                        {formatDate(
                                                            news[0].published_at,
                                                        )}
                                                    </time>
                                                </div>
                                                <Link
                                                    href={news[0].url}
                                                    className="block text-xl sm:text-2xl font-bold tracking-tight text-slate-900 transition-colors group-hover:text-cyan-700"
                                                >
                                                    {news[0].title}
                                                </Link>
                                                {news[0].excerpt && (
                                                    <p className="line-clamp-3 text-sm text-slate-600 leading-relaxed">
                                                        {news[0].excerpt}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="pt-2">
                                                <Link
                                                    href={news[0].url}
                                                    className="inline-flex items-center gap-1 text-sm font-bold text-cyan-700 hover:underline"
                                                >
                                                    Read Article
                                                    <ArrowRight className="size-3.5" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-4">
                                    {(news ?? []).slice(1).map((item) => (
                                        <div
                                            key={item.slug}
                                            className="glass-panel-light glass-card-hover group rounded-2xl p-4 shadow-sm"
                                        >
                                            <div className="flex gap-4">
                                                {item.cover_url && (
                                                    <img
                                                        src={item.cover_url}
                                                        alt=""
                                                        loading="lazy"
                                                        className="size-20 shrink-0 rounded-xl object-cover transition-transform duration-300 group-hover:scale-105"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1 space-y-1">
                                                    <p className="text-xs text-slate-500">
                                                        {formatDate(
                                                            item.published_at,
                                                        )}
                                                    </p>
                                                    <Link
                                                        href={item.url}
                                                        className="line-clamp-2 block text-sm font-bold text-slate-900 transition-colors group-hover:text-cyan-700"
                                                    >
                                                        {item.title}
                                                    </Link>
                                                    {item.excerpt && (
                                                        <p className="line-clamp-2 text-xs text-slate-600">
                                                            {item.excerpt}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── 8. Alumni Stories & Voices (Glass Testimonials) ───── */}
                    <Deferred data="stories" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.featured_alumni')}
                            href="/stories"
                            linkLabel={t('public.home.all_stories')}
                            empty={(stories ?? []).length === 0}
                        >
                            <div className="grid gap-5 sm:grid-cols-2">
                                {(stories ?? []).map((story) => (
                                    <div
                                        key={story.slug}
                                        className="glass-panel-light glass-card-hover group rounded-2xl p-6 shadow-sm"
                                    >
                                        <div className="flex items-start gap-4">
                                            {story.photo_url ? (
                                                <img
                                                    src={story.photo_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="size-16 shrink-0 rounded-full object-cover ring-2 ring-cyan-500/50 shadow-md"
                                                />
                                            ) : (
                                                <div className="flex size-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-lg font-bold text-white shadow-md ring-2 ring-cyan-400/50">
                                                    {story.author_name.charAt(
                                                        0,
                                                    )}
                                                </div>
                                            )}
                                            <div className="min-w-0 flex-1 space-y-1">
                                                <Link
                                                    href={story.url}
                                                    className="block text-base font-bold text-slate-900 transition-colors group-hover:text-cyan-700"
                                                >
                                                    {story.title}
                                                </Link>
                                                <p className="text-xs font-semibold text-cyan-800">
                                                    {t(
                                                        'public.stories.by',
                                                        {
                                                            name: story.author_name,
                                                        },
                                                    )}
                                                    {story.career_summary
                                                        ? ` · ${story.career_summary}`
                                                        : ''}
                                                </p>
                                                <p className="mt-2 line-clamp-3 text-xs italic text-slate-600 leading-relaxed">
                                                    "{story.excerpt}"
                                                </p>
                                                <div className="pt-2">
                                                    <Link
                                                        href={story.url}
                                                        className="inline-flex items-center gap-1 text-xs font-bold text-cyan-700 hover:underline"
                                                    >
                                                        Read Alumni Journey
                                                        <ArrowRight className="size-3" />
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── 9. Campus Memories (Glass Photography Frame) ───────── */}
                    <Deferred data="photos" fallback={<CardsSkeleton />}>
                        <Section
                            title={t('public.sections.recent_photos')}
                            href="/gallery"
                            linkLabel={t('public.home.all_photos')}
                            empty={(photos ?? []).length === 0}
                        >
                            <div className="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                                {(photos ?? []).map((photo) => (
                                    <Link
                                        key={photo.id}
                                        href={photo.url}
                                        className="glass-panel-light group relative aspect-[4/3] overflow-hidden rounded-2xl shadow-sm transition-all hover:scale-[1.02] hover:shadow-md"
                                    >
                                        <img
                                            src={photo.thumb_url}
                                            alt={photo.caption ?? photo.album}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-108"
                                        />
                                        <div className="glass-panel-dark absolute inset-0 flex flex-col justify-end p-3.5 text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                            <p className="line-clamp-1 text-xs font-bold">
                                                {photo.caption ?? photo.album}
                                            </p>
                                            <p className="mt-0.5 flex items-center gap-1 text-[0.7rem] text-cyan-200">
                                                <Images className="size-3" />
                                                <span className="truncate">
                                                    {photo.album}
                                                </span>
                                            </p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </Section>
                    </Deferred>

                    {/* ── 10. School Heritage Timeline (Glass Milestone Cards) ─ */}
                    <Deferred data="timeline" fallback={<RowSkeleton />}>
                        <Section
                            title={t('public.sections.timeline')}
                            href="/about/school"
                            linkLabel={t('public.home.full_timeline')}
                            empty={(timeline ?? []).length === 0}
                        >
                            <ol className="grid gap-4 sm:grid-cols-4">
                                {(timeline ?? []).map((milestone, idx) => (
                                    <li
                                        key={milestone.year}
                                        className="glass-panel-light glass-card-hover relative rounded-2xl p-5 shadow-sm"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-2xl font-extrabold text-slate-900">
                                                {milestone.year}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="glass-pill-light border-cyan-500/40 text-[0.65rem] font-bold text-cyan-800"
                                            >
                                                Milestone {idx + 1}
                                            </Badge>
                                        </div>
                                        <p className="mt-2 text-sm font-bold text-slate-800">
                                            {milestone.title}
                                        </p>
                                        {milestone.description && (
                                            <p className="mt-1.5 line-clamp-3 text-xs text-slate-600 leading-relaxed">
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

            {/* ── 11. Grand Finale Community Join Banner (Glassmorphic Portal) ── */}
            <section className="relative overflow-hidden bg-gradient-to-b from-[#060b18] via-[#091329] to-[#0c1836] text-white">
                <div
                    className="pointer-events-none absolute -bottom-20 left-1/2 -translate-x-1/2 size-[600px] rounded-full opacity-30 blur-[130px]"
                    style={{
                        background:
                            'radial-gradient(circle, rgba(6, 182, 212, 0.6) 0%, rgba(79, 70, 229, 0.4) 60%, transparent 80%)',
                    }}
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-16 text-center sm:py-24">
                    <div className="glass-panel-dark mx-auto rounded-3xl p-8 sm:p-14 shadow-2xl border-cyan-500/30">
                        <Badge className="glass-pill mb-4 border-amber-500/40 bg-amber-500/20 px-3.5 py-1 text-xs font-bold text-amber-300">
                            Alumni Gateway
                        </Badge>

                        <h2 className="text-2xl font-extrabold tracking-tight sm:text-4xl text-white">
                            Once a Student, Forever an Alumnus.
                        </h2>

                        <p className="mx-auto mt-4 max-w-2xl text-base text-white/85 sm:text-lg">
                            {t('public.home.join_body')}
                        </p>

                        {jubilee?.seats_left != null && (
                            <p className="mt-3 text-sm font-bold text-amber-400">
                                {choice(
                                    'public.home.seats_left',
                                    jubilee.seats_left,
                                )}
                            </p>
                        )}

                        <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                            <Button
                                size="lg"
                                className="h-12 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-8 text-base font-bold text-slate-950 shadow-[0_10px_25px_-5px_rgba(245,158,11,0.4)] transition-all hover:scale-[1.03] hover:from-amber-400 hover:to-amber-500"
                                asChild
                            >
                                <Link href="/join">
                                    {t('public.hero.cta_primary')}
                                    <ArrowRight className="ms-2 size-4" />
                                </Link>
                            </Button>

                            <Button
                                size="lg"
                                variant="outline"
                                className="glass-pill h-12 rounded-xl border-white/25 px-6 text-base font-medium text-white hover:bg-white/20 hover:text-white"
                                asChild
                            >
                                <Link href="/login">
                                    {t('common.actions.login')}
                                </Link>
                            </Button>
                        </div>

                        <p className="mt-6 text-xs text-white/60">
                            Takes ~5 minutes • Verified by executive committee • Confidential & privacy-protected
                        </p>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

function StatCard({
    icon: Icon,
    iconBg,
    glowColor = 'from-teal-500/10 to-transparent',
    value,
    label,
    sublabel,
}: {
    icon: ComponentType<{ className?: string; 'aria-hidden'?: boolean | 'true' | 'false' }>;
    iconBg: string;
    glowColor?: string;
    value: number;
    label: string;
    sublabel: string;
}) {
    return (
        <div className="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] p-4.5 text-center shadow-lg backdrop-blur-xl transition-all duration-300 hover:border-cyan-400/40 hover:bg-white/[0.08] hover:-translate-y-1 hover:shadow-[0_15px_30px_-5px_rgba(6,182,212,0.25)]">
            {/* Ambient Card Glow */}
            <div
                aria-hidden="true"
                className={`pointer-events-none absolute -top-10 left-1/2 -translate-x-1/2 h-20 w-32 rounded-full bg-gradient-to-b ${glowColor} blur-xl opacity-0 transition-opacity duration-300 group-hover:opacity-100`}
            />

            <div className="relative flex flex-col items-center">
                <div
                    className={`flex size-12 items-center justify-center rounded-2xl shadow-inner backdrop-blur-md transition-transform duration-300 group-hover:scale-110 ${iconBg}`}
                >
                    <Icon className="size-5.5 drop-shadow-sm" aria-hidden="true" />
                </div>
                <p className="mt-3.5 bg-gradient-to-r from-white via-slate-100 to-cyan-100 bg-clip-text text-2xl font-black tabular-nums tracking-tight text-transparent sm:text-3xl">
                    {formatNumber(value)}
                </p>
                <p className="mt-0.5 text-xs font-bold tracking-wide text-cyan-300 sm:text-sm">
                    {label}
                </p>
                <p className="mt-1 hidden text-[0.75rem] leading-snug text-slate-400 transition-colors group-hover:text-slate-200 sm:block">
                    {sublabel}
                </p>
            </div>
        </div>
    );
}

function PillarCard({
    icon: Icon,
    iconBg,
    title,
    description,
    link,
    linkText,
}: {
    icon: ComponentType<{ className?: string; 'aria-hidden'?: boolean | 'true' | 'false' }>;
    iconBg: string;
    title: string;
    description: string;
    link: string;
    linkText: string;
}) {
    return (
        <div className="glass-panel-light glass-card-hover flex flex-col justify-between rounded-2xl p-5 shadow-xs">
            <div>
                <div
                    className={`flex size-11 items-center justify-center rounded-xl shadow-xs ${iconBg}`}
                >
                    <Icon className="size-5" aria-hidden="true" />
                </div>
                <h3 className="mt-3.5 text-base font-bold text-slate-900">
                    {title}
                </h3>
                <p className="mt-1.5 text-xs text-slate-600 leading-relaxed">
                    {description}
                </p>
            </div>
            <div className="mt-4 pt-3 border-t border-slate-200/60">
                <Link
                    href={link}
                    className="inline-flex items-center gap-1 text-xs font-bold text-cyan-700 hover:underline"
                >
                    {linkText}
                    <ArrowRight className="size-3" />
                </Link>
            </div>
        </div>
    );
}

/**
 * A section that renders nothing when it has no items.
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
            <div className="mb-5 flex items-center justify-between gap-3">
                <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    {title}
                </h2>
                <Link
                    href={href}
                    className="inline-flex items-center gap-1 text-sm font-bold text-cyan-700 hover:underline"
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
        <section className="space-y-3">
            {items.map((announcement) => (
                <div
                    key={announcement.id}
                    className="glass-panel-light flex items-start gap-3 rounded-2xl border-amber-500/30 p-4 shadow-sm"
                >
                    <div className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-cyan-500/15 text-cyan-700 border border-cyan-500/30">
                        <Megaphone className="size-4" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="text-sm font-bold text-slate-900">
                                {announcement.title}
                            </p>
                            {announcement.starts_at && (
                                <span className="text-[0.7rem] text-slate-500">
                                    • {formatDate(announcement.starts_at)}
                                </span>
                            )}
                        </div>
                        <p className="mt-0.5 text-xs text-slate-600 whitespace-pre-wrap leading-relaxed">
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
                            className="ms-auto shrink-0 text-xs font-bold"
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
                <Skeleton key={key} className="h-44 w-full rounded-2xl" />
            ))}
        </div>
    );
}

function RowSkeleton() {
    return <Skeleton className="h-16 w-full rounded-2xl" />;
}
