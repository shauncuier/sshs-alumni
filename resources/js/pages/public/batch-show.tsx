import { Link } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, Lock, Sparkles, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Batch = {
    slug: string;
    name: string;
    description: string | null;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};

/**
 * A single batch page for the public.
 *
 * Shows the count and invites people in. The member list lives behind the
 * directory, which requires an approved membership.
 */
export default function BatchShow({ batch }: { batch: Batch }) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout
            title={batch.name}
            description={batch.description ?? undefined}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                    >
                        <Link href="/batches">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.nav.batches')}
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center gap-3">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                            <GraduationCap className="size-3.5" />
                            SSC Class of {batch.ssc_year}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3.5 py-1 text-xs font-medium text-cyan-200 backdrop-blur-md">
                            <Users className="size-3.5" aria-hidden="true" />
                            {choice(
                                'public.batches.member_count',
                                batch.members_count,
                                { count: formatNumber(batch.members_count) },
                            )}
                        </span>
                    </div>

                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {batch.name}
                    </h1>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {batch.description && (
                        <div className="glass-panel-light rounded-3xl p-6 sm:p-8 mb-8 border border-teal-500/15">
                            <p className="text-base sm:text-lg leading-relaxed text-slate-700">
                                {batch.description}
                            </p>
                        </div>
                    )}

                    {/* Directory Lock Callout */}
                    <div className="relative overflow-hidden rounded-3xl border border-teal-500/25 bg-gradient-to-br from-teal-50 via-white to-cyan-50/40 p-6 sm:p-8 shadow-sm backdrop-blur-md">
                        <div className="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                            <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-md shadow-teal-700/20">
                                <Lock className="size-6" aria-hidden="true" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-base sm:text-lg font-bold text-slate-900">
                                    Members Directory is Protected
                                </h3>
                                <p className="mt-1 text-sm text-slate-600 leading-relaxed">
                                    {t('public.directory.members_only_note')}
                                </p>
                            </div>
                            <Button
                                asChild
                                className="w-full sm:w-auto shrink-0 bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 text-white font-semibold shadow-md shadow-teal-500/25 hover:from-teal-600 hover:to-cyan-700 rounded-xl px-6 py-2.5 transition"
                            >
                                <Link href="/join">
                                    <Sparkles className="mr-2 size-4" />
                                    {t('public.nav.join')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
