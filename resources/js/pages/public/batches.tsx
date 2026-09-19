import { Link } from '@inertiajs/react';
import { ChevronRight, GraduationCap, Lock, Users } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Batch = {
    slug: string;
    name: string;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};

type Props = {
    batches: Batch[];
    totals: { batches: number; members: number };
};

/**
 * Public batch index — COUNTS ONLY.
 *
 * The directory is members-only. Listing real names here would hand a scraper
 * exactly what the directory withholds, so this page says how many and invites
 * people to join rather than showing who.
 */
export default function Batches({ batches, totals }: Props) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout
            title={t('public.nav.batches')}
            description={t('public.batches.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <GraduationCap className="size-3.5" />
                        Alumni Generations
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.nav.batches')}
                    </h1>
                    <p className="mt-3 max-w-2xl mx-auto text-base sm:text-lg text-slate-300">
                        {t('public.batches.subtitle')}
                    </p>

                    {/* Stats display */}
                    <div className="mt-8 flex justify-center gap-6 sm:gap-10">
                        <div className="rounded-2xl border border-white/10 bg-white/5 px-6 py-4 backdrop-blur-md shadow-inner">
                            <dd className="text-3xl sm:text-4xl font-extrabold text-teal-400">
                                {formatNumber(totals.batches)}
                            </dd>
                            <dt className="text-xs sm:text-sm font-medium uppercase tracking-wider text-slate-300 mt-1">
                                {t('public.stats.batches')}
                            </dt>
                        </div>
                        <div className="rounded-2xl border border-white/10 bg-white/5 px-6 py-4 backdrop-blur-md shadow-inner">
                            <dd className="text-3xl sm:text-4xl font-extrabold text-cyan-400">
                                {formatNumber(totals.members)}
                            </dd>
                            <dt className="text-xs sm:text-sm font-medium uppercase tracking-wider text-slate-300 mt-1">
                                {t('public.stats.members')}
                            </dt>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Privacy Note */}
                    <div className="mb-10 flex items-center gap-3.5 rounded-2xl border border-teal-500/20 bg-teal-50/60 p-4 sm:p-5 text-sm text-teal-950 backdrop-blur-md shadow-sm">
                        <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white shadow-sm">
                            <Lock className="size-4" aria-hidden="true" />
                        </div>
                        <p className="leading-relaxed font-medium">
                            {t('public.directory.members_only_note')}
                        </p>
                    </div>

                    {/* Batches Grid */}
                    <div className="grid gap-4 sm:gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {batches.map((batch) => (
                            <Link
                                key={batch.slug}
                                href={`/batches/${batch.slug}`}
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-teal-500/15 bg-white/90 p-5 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-lg"
                            >
                                <div className="flex items-center gap-4">
                                    <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500/10 to-teal-500/20 text-teal-700 transition duration-300 group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white">
                                        <GraduationCap className="size-6" aria-hidden="true" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-base font-bold text-slate-900 group-hover:text-teal-900 transition">
                                            {batch.name}
                                        </p>
                                        <div className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                            <Users className="size-3.5 text-teal-600/70" />
                                            <span>
                                                {choice(
                                                    'public.batches.member_count',
                                                    batch.members_count,
                                                    {
                                                        count: formatNumber(
                                                            batch.members_count,
                                                        ),
                                                    },
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                    <ChevronRight className="size-4 text-slate-400 transition-transform duration-300 group-hover:translate-x-1 group-hover:text-teal-600" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
