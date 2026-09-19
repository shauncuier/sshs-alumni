import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { EventDate } from '@/components/public/event-date';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import PublicLayout from '@/layouts/public-layout';
import type { PublicEvent } from '@/types/event';

type Props = {
    event: PublicEvent | null;
    active: 'overview' | 'schedule' | 'sponsors' | 'faq';
    title: string;
    children: ReactNode;
};

const TABS = [
    { key: 'overview', href: '/jubilee' },
    { key: 'schedule', href: '/jubilee/schedule' },
    { key: 'sponsors', href: '/jubilee/sponsors' },
    { key: 'faq', href: '/jubilee/faq' },
] as const;

/**
 * The shell shared by the Jubilee's inner pages.
 *
 * Gold is used here and nowhere else on the site — it appears on neither logo,
 * so it only carries meaning on the সুবর্ণজয়ন্তী pages.
 *
 * @see docs/07-branding-ui.md section 4
 */
export function JubileeLayout({ event, active, title, children }: Props) {
    const { t } = useTranslation();

    const titleBn = useSetting<string>('jubilee.title_bn');
    const titleEn = useSetting<string>('jubilee.title_en');

    return (
        <PublicLayout title={`${title} — ${titleEn ?? t('jubilee.title')}`}>
            <header className="relative overflow-hidden bg-gradient-to-b from-[#080d1e] via-[#0b1329] to-[#0f1d3d] text-white">
                {/* Ambient glow orbs */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-28 -left-20 h-80 w-80 rounded-full bg-cyan-500/15 blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/4 -right-24 h-80 w-80 rounded-full bg-amber-500/15 blur-3xl"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-12 sm:py-16">
                    {/* Golden Jubilee Pill Badge */}
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-3.5 py-1 text-xs font-semibold tracking-wide text-amber-300 uppercase shadow-inner backdrop-blur-md">
                        <span className="size-2 rounded-full bg-amber-400 animate-pulse" />
                        <span>Golden Jubilee • সুবর্ণজয়ন্তী</span>
                    </div>

                    {titleBn && (
                        <h1
                            lang="bn"
                            className="bg-gradient-to-r from-amber-200 via-amber-400 to-amber-100 bg-clip-text text-3xl font-extrabold text-transparent drop-shadow-sm sm:text-4xl"
                        >
                            {titleBn}
                        </h1>
                    )}
                    <p className="mt-1 text-sm font-medium tracking-wide text-slate-300 sm:text-base">
                        {titleEn ?? t('jubilee.title')}
                    </p>

                    {/* THE DATE RULE, on every page of the microsite. */}
                    {event && (
                        <div className="mt-4 inline-block">
                            <EventDate
                                event={event}
                                className="glass-panel-dark inline-flex items-center gap-2 rounded-full border-amber-400/30 px-4 py-1.5 text-xs text-amber-200"
                            />
                        </div>
                    )}

                    {/* Glassmorphic Navigation Tabs */}
                    <nav
                        className="mt-8 inline-flex flex-wrap items-center gap-1.5 rounded-2xl border border-white/10 bg-white/5 p-1.5 shadow-xl backdrop-blur-xl"
                        aria-label={t('jubilee.title')}
                    >
                        {TABS.map((tab) => (
                            <Link
                                key={tab.key}
                                href={tab.href}
                                className={cn(
                                    'rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
                                    active === tab.key
                                        ? 'bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 text-slate-950 font-bold shadow-md shadow-amber-500/25'
                                        : 'text-slate-300 hover:bg-white/10 hover:text-white',
                                )}
                            >
                                {t(`jubilee.nav.${tab.key}`)}
                            </Link>
                        ))}
                    </nav>
                </div>
            </header>

            <div className="relative min-h-[50vh] bg-gradient-to-b from-[#f0f7f9] via-white to-[#f0f7f9]">
                <div className="relative mx-auto max-w-4xl px-4 py-12 sm:py-16">
                    <div className="mb-8 flex items-center gap-3">
                        <div className="h-8 w-1.5 rounded-full bg-gradient-to-b from-amber-500 to-teal-600" />
                        <h2 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            {title}
                        </h2>
                    </div>

                    <div className="glass-panel-light rounded-3xl border border-teal-500/15 p-6 shadow-sm sm:p-8">
                        {children}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
