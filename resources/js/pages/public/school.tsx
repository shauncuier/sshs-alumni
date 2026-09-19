import { BookOpen, Calendar, Clock, GraduationCap, MapPin, School as SchoolIcon, ScrollText, Sparkles } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatYear } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Milestone } from '@/types/content';

type Props = {
    school: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
        eiin: string | null;
        board: string | null;
        address: string | null;
        head_teacher: string | null;
        motto_en: string | null;
    };
    milestones: Milestone[];
};

/**
 * The school's fifty years.
 *
 * The timeline is seeded with two entries and says so. A timeline with two
 * milestones and no explanation looks broken; one that states outright that
 * the rest is still being collected looks like what it is, and invites the
 * people who remember to fill it in.
 */
export default function School({ school, milestones }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.school.title')}
            description={t('public.school.subtitle')}
        >
            {/* ── Hero Section ────────────────────────────────────────────── */}
            <header className="relative overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 h-96 w-[42rem] rounded-full bg-gradient-to-tr from-amber-500/15 via-teal-500/15 to-transparent blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute bottom-10 right-10 h-72 w-72 rounded-full bg-teal-500/10 blur-3xl"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-16 text-center sm:py-20">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-4 py-1.5 text-xs font-semibold text-amber-300 shadow-inner backdrop-blur-md">
                        <SchoolIcon className="size-3.5 text-amber-400" />
                        <span>Alma Mater • আমাদের বিদ্যাপীঠ (প্রতিষ্ঠা ১৯৭৬)</span>
                    </div>

                    <h1 className="bg-gradient-to-r from-white via-slate-100 to-slate-200 bg-clip-text text-3xl font-extrabold text-transparent sm:text-5xl">
                        {school.name_en ?? t('public.school.title')}
                    </h1>

                    {school.name_bn && (
                        <p
                            className="font-bangla mt-3 text-2xl font-bold text-amber-300"
                            lang="bn"
                        >
                            {school.name_bn}
                        </p>
                    )}

                    {school.motto_en && (
                        <p className="mt-3 text-sm italic text-teal-300/90 sm:text-base">
                            "{school.motto_en}"
                        </p>
                    )}

                    {/* School Key Facts Grid */}
                    <dl className="mt-8 grid gap-3 text-start sm:grid-cols-3">
                        {school.established && (
                            <Fact
                                icon={<Calendar className="size-4 text-amber-400" />}
                                label={t('public.school.founded')}
                                value={formatYear(school.established)}
                            />
                        )}
                        {school.eiin && (
                            <Fact
                                icon={<Sparkles className="size-4 text-teal-400" />}
                                label="EIIN Registry"
                                value={String(school.eiin)}
                            />
                        )}
                        {school.board && (
                            <Fact
                                icon={<BookOpen className="size-4 text-cyan-400" />}
                                label={t('public.school.board')}
                                value={school.board}
                            />
                        )}
                        {school.head_teacher && (
                            <Fact
                                icon={<GraduationCap className="size-4 text-teal-400" />}
                                label={t('public.school.head_teacher')}
                                value={school.head_teacher}
                            />
                        )}
                        {school.address && (
                            <div className="sm:col-span-2">
                                <Fact
                                    icon={<MapPin className="size-4 text-amber-400" />}
                                    label="Campus Location"
                                    value={school.address}
                                />
                            </div>
                        )}
                    </dl>
                </div>
            </header>

            {/* ── Timeline Section ────────────────────────────────────────── */}
            <div className="relative min-h-[60vh] bg-gradient-to-b from-[#f0f7f9] via-white to-[#f0f7f9] py-12 sm:py-16">
                <div className="relative mx-auto max-w-4xl px-4">
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="mb-8 flex items-center gap-3 border-b border-teal-100/60 pb-5">
                            <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-md shadow-amber-500/20">
                                <Clock className="size-5" />
                            </div>
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                                    {t('public.school.timeline')}
                                </h2>
                                <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                    ইতিহাস ও স্মরণীয় মুহূর্তসমূহ
                                </p>
                            </div>
                        </div>

                        {milestones.length === 0 ? (
                            <p className="mt-4 text-sm text-slate-500">
                                {t('public.school.timeline_empty')}
                            </p>
                        ) : (
                            <>
                                <ol className="relative mt-6 space-y-8 border-s-2 border-teal-200/80 ps-8">
                                    {milestones.map((milestone) => (
                                        <li
                                            key={milestone.id ?? milestone.year}
                                            className="relative group"
                                        >
                                            <span
                                                aria-hidden="true"
                                                className={cn(
                                                    'absolute -start-[2.55rem] top-1.5 size-5 rounded-full border-4 border-white shadow-md',
                                                    milestone.is_highlighted
                                                        ? 'bg-amber-500 ring-2 ring-amber-400/50 shadow-amber-500/30'
                                                        : 'bg-teal-600',
                                                )}
                                            />

                                            <div className="glass-card-hover rounded-2xl border border-teal-500/10 bg-white/70 p-6 shadow-xs transition-all duration-200 hover:border-teal-500/25">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-3 py-0.5 text-xs font-bold text-slate-950 shadow-xs">
                                                        {milestone.year}
                                                        {milestone.date_label
                                                            ? ` · ${milestone.date_label}`
                                                            : ''}
                                                    </span>
                                                </div>

                                                <h3 className="mt-2 text-base font-bold text-slate-900 sm:text-lg">
                                                    {milestone.title}
                                                </h3>

                                                {milestone.description && (
                                                    <p className="mt-1.5 text-sm leading-relaxed text-slate-600">
                                                        {milestone.description}
                                                    </p>
                                                )}

                                                {milestone.image_url && (
                                                    <img
                                                        src={milestone.image_url}
                                                        alt=""
                                                        loading="lazy"
                                                        className="mt-4 h-48 w-full rounded-2xl object-cover shadow-sm sm:w-96"
                                                    />
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ol>

                                <div className="mt-8 flex items-start gap-3 rounded-2xl border border-dashed border-teal-200 bg-teal-50/50 p-4 text-xs text-slate-600 sm:text-sm">
                                    <ScrollText
                                        className="mt-0.5 size-4 shrink-0 text-teal-700"
                                        aria-hidden="true"
                                    />
                                    <span>{t('public.school.timeline_partial')}</span>
                                </div>
                            </>
                        )}
                    </section>
                </div>
            </div>
        </PublicLayout>
    );
}

function Fact({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
    return (
        <div className="glass-panel-dark flex items-start gap-3 rounded-2xl border border-white/10 p-3.5 shadow-sm backdrop-blur-md">
            <div className="mt-0.5 shrink-0">{icon}</div>
            <div>
                {label && <dt className="text-[0.7rem] font-semibold tracking-wider text-slate-400 uppercase">{label}</dt>}
                <dd className="text-xs font-bold text-white sm:text-sm">{value}</dd>
            </div>
        </div>
    );
}
