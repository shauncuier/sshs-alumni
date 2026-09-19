import { Deferred, Link } from '@inertiajs/react';
import { ArrowRight, CircleHelp, GraduationCap, HelpCircle, School, Sparkles, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { formatYear } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import type { Faq } from '@/types/content';

type Props = {
    organisation: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
    };
    school: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
        eiin: string | null;
        address: string | null;
    };
    stats?: { milestones: number; albums: number };
    faqs?: Faq[];
};

export default function About({ organisation, school, faqs }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.about.title')}
            description={t('public.about.subtitle')}
        >
            {/* ── Hero Section ────────────────────────────────────────────── */}
            <header className="relative overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 h-96 w-[40rem] rounded-full bg-gradient-to-tr from-teal-500/15 via-cyan-500/15 to-transparent blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/3 -right-20 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-16 text-center sm:py-20">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-4 py-1.5 text-xs font-semibold text-teal-300 shadow-inner backdrop-blur-md">
                        <Users className="size-3.5 text-teal-400" />
                        <span>About the Association • প্রাক্তন ছাত্র-ছাত্রী পরিষদ</span>
                    </div>

                    <h1 className="bg-gradient-to-r from-white via-slate-100 to-slate-200 bg-clip-text text-3xl font-extrabold text-transparent sm:text-5xl">
                        {t('public.about.title')}
                    </h1>
                    <p className="mx-auto mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                        {t('public.about.subtitle')}
                    </p>
                </div>
            </header>

            {/* ── Main Content ────────────────────────────────────────────── */}
            <div className="relative min-h-[60vh] bg-gradient-to-b from-[#f0f7f9] via-white to-[#f0f7f9] py-12 sm:py-16">
                <div className="relative mx-auto max-w-4xl space-y-10 px-4">
                    {/* ── Association Section ─────────────────────────────────── */}
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white shadow-md shadow-teal-500/20">
                                <Users className="size-5" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                    {t('public.about.association')}
                                </h2>
                                <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                                    অ্যালামনাই নেটওয়ার্ক ও ভ্রাতৃত্ব
                                </p>
                            </div>
                        </div>

                        {organisation.name_bn && (
                            <p
                                className="font-bangla mt-4 text-xl font-bold text-teal-900"
                                lang="bn"
                            >
                                {organisation.name_bn}
                            </p>
                        )}

                        <p className="mt-3 text-base leading-relaxed text-slate-700">
                            {t('public.about.association_body')}
                        </p>

                        {organisation.established && (
                            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-50 px-3.5 py-1 text-xs font-semibold text-teal-800">
                                <Sparkles className="size-3 text-amber-500" />
                                <span>
                                    {t('public.about.founded', {
                                        year: organisation.established,
                                    })}
                                </span>
                            </div>
                        )}
                    </section>

                    {/* ── School Section ──────────────────────────────────────── */}
                    <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-md shadow-amber-500/20">
                                <School className="size-5" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                    {t('public.about.school')}
                                </h2>
                                <p className="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                                    বিদ্যাপীঠের গৌরবময় পরিচিতি
                                </p>
                            </div>
                        </div>

                        {school.name_bn && (
                            <p
                                className="font-bangla mt-4 text-xl font-bold text-slate-900"
                                lang="bn"
                            >
                                {school.name_bn}
                            </p>
                        )}

                        <dl className="mt-5 grid gap-4 rounded-2xl border border-teal-500/10 bg-white/70 p-5 text-sm sm:grid-cols-2">
                            {school.name_en && (
                                <Detail label="Official English Name" value={school.name_en} />
                            )}
                            {school.established && (
                                <Detail
                                    label={t('public.school.founded')}
                                    value={formatYear(school.established)}
                                />
                            )}
                            {school.eiin && (
                                <Detail
                                    label="EIIN Number"
                                    value={String(school.eiin)}
                                />
                            )}
                            {school.address && (
                                <Detail label="Campus Location" value={school.address} />
                            )}
                        </dl>

                        <div className="mt-6">
                            <Button
                                asChild
                                variant="outline"
                                className="rounded-xl border-teal-500/30 text-teal-800 hover:bg-teal-50 font-semibold"
                            >
                                <Link href="/about/school" className="flex items-center gap-1.5">
                                    <span>{t('public.about.school_link')}</span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </section>

                    {/* ── FAQ Section ─────────────────────────────────────────── */}
                    <Deferred
                        data="faqs"
                        fallback={<Skeleton className="h-40 w-full rounded-3xl" />}
                    >
                        <Faqs items={faqs ?? []} />
                    </Deferred>

                    {/* ── Contact CTA Card ────────────────────────────────────── */}
                    <section className="glass-panel-light relative overflow-hidden rounded-3xl border border-teal-500/20 p-8 text-center shadow-sm sm:p-10">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-teal-100 text-teal-800 shadow-sm">
                            <CircleHelp className="size-6" aria-hidden="true" />
                        </div>
                        <h3 className="mt-4 text-lg font-bold text-slate-900 sm:text-xl">
                            {t('public.about.contact_cta')}
                        </h3>
                        <p className="mt-1 text-xs text-slate-500">
                            Have questions or need assistance? Reach out to our executive secretariat.
                        </p>
                        <div className="mt-5">
                            <Button
                                asChild
                                className="rounded-xl bg-gradient-to-r from-teal-600 via-teal-500 to-cyan-600 px-6 font-bold text-white shadow-md shadow-teal-500/20 hover:from-teal-500 hover:to-cyan-500"
                            >
                                <Link href="/contact">
                                    {t('public.nav.contact')}
                                </Link>
                            </Button>
                        </div>
                    </section>
                </div>
            </div>
        </PublicLayout>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            {label && (
                <dt className="text-xs font-semibold text-slate-500 uppercase tracking-wide">{label}</dt>
            )}
            <dd className="mt-0.5 font-bold text-slate-800">{value}</dd>
        </div>
    );
}

function Faqs({ items }: { items: Faq[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="glass-panel-light rounded-3xl border border-teal-500/15 p-8 shadow-sm sm:p-10">
            <div className="mb-6 flex items-center gap-3 border-b border-teal-100/60 pb-5">
                <div className="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-600 to-slate-800 text-white shadow-md">
                    <HelpCircle className="size-5" />
                </div>
                <div>
                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                        {t('public.about.faqs')}
                    </h2>
                    <p className="text-xs font-semibold tracking-wider text-teal-700 uppercase">
                        সাধারণ প্রশ্নোত্তর
                    </p>
                </div>
            </div>

            <div className="space-y-4">
                {items.map((faq) => (
                    <div
                        key={faq.id}
                        className="glass-card-hover rounded-2xl border border-teal-500/15 bg-white/80 p-5 shadow-xs transition-all duration-200 hover:border-teal-500/30"
                    >
                        <p className="flex items-start gap-2.5 font-bold text-slate-900">
                            <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">
                                Q
                            </span>
                            <span>{faq.question}</span>
                        </p>
                        <p className="mt-2 ps-7.5 text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">
                            {faq.answer}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}
