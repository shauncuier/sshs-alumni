import { Link } from '@inertiajs/react';
import { CheckCircle2, Home, LogIn, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';

export default function JoinDone() {
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.join.done.title')} indexable={false}>
            <div className="relative min-h-[75vh] overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] py-20 text-white flex items-center justify-center">
                {/* Ambient glow spheres */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 h-96 w-[36rem] rounded-full bg-gradient-to-tr from-teal-500/20 via-cyan-500/20 to-transparent blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute bottom-10 right-1/4 h-80 w-80 rounded-full bg-amber-500/15 blur-3xl"
                />

                <div className="relative mx-auto max-w-xl px-4 text-center">
                    <div className="glass-panel-dark relative overflow-hidden rounded-3xl border border-teal-400/30 p-8 shadow-2xl backdrop-blur-2xl sm:p-12">
                        {/* Decorative Top Accent */}
                        <div
                            aria-hidden="true"
                            className="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-amber-400 via-teal-500 to-cyan-500"
                        />

                        {/* Animated Glowing Checkmark */}
                        <div className="mx-auto mb-6 flex size-20 items-center justify-center rounded-3xl bg-gradient-to-br from-teal-400 to-cyan-500 text-slate-950 shadow-xl shadow-teal-500/30 ring-4 ring-teal-400/20">
                            <CheckCircle2 className="size-10 stroke-[2.5]" />
                        </div>

                        <div className="mb-4 inline-flex items-center gap-1.5 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold text-teal-300">
                            <Sparkles className="size-3.5 text-amber-400" />
                            <span>Registration Complete • অভিনন্দন</span>
                        </div>

                        <h1 className="bg-gradient-to-r from-white via-slate-100 to-slate-200 bg-clip-text text-2xl font-extrabold text-transparent sm:text-3xl">
                            {t('public.join.done.title')}
                        </h1>

                        <p className="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                            {t('public.join.done.body')}
                        </p>

                        <div className="mt-8 flex flex-wrap justify-center gap-4">
                            <Button
                                asChild
                                size="lg"
                                className="h-12 rounded-xl bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 px-8 text-sm font-bold text-white shadow-lg shadow-teal-500/25 transition-all duration-300 hover:from-teal-400 hover:to-cyan-500 hover:shadow-xl hover:shadow-teal-500/35 hover:-translate-y-0.5"
                            >
                                <Link href="/login" className="flex items-center gap-2">
                                    <LogIn className="size-4" />
                                    <span>{t('common.actions.login')}</span>
                                </Link>
                            </Button>

                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="h-12 rounded-xl border-white/20 bg-white/5 px-6 text-sm font-semibold text-white backdrop-blur-md transition-all duration-300 hover:border-white/40 hover:bg-white/15 hover:text-white"
                            >
                                <Link href="/" className="flex items-center gap-2">
                                    <Home className="size-4 text-teal-300" />
                                    <span>{t('public.nav.home')}</span>
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
