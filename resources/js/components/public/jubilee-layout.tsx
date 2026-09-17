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
            <header className="from-brand-green-900 to-brand-green-800 bg-gradient-to-b text-white">
                <div className="mx-auto max-w-4xl px-4 py-10">
                    {titleBn && (
                        <p
                            lang="bn"
                            className="text-brand-gold-500 text-2xl font-bold"
                        >
                            {titleBn}
                        </p>
                    )}
                    <p className="text-sm text-white/60">
                        {titleEn ?? t('jubilee.title')}
                    </p>

                    {/* THE DATE RULE, on every page of the microsite. */}
                    {event && (
                        <EventDate
                            event={event}
                            className="mt-4 text-sm text-white/80"
                        />
                    )}

                    <nav
                        className="mt-6 flex flex-wrap gap-1"
                        aria-label={t('jubilee.title')}
                    >
                        {TABS.map((tab) => (
                            <Link
                                key={tab.key}
                                href={tab.href}
                                className={cn(
                                    'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    active === tab.key
                                        ? 'text-brand-green-900 bg-brand-gold-500'
                                        : 'text-white/70 hover:bg-white/10 hover:text-white',
                                )}
                            >
                                {t(`jubilee.nav.${tab.key}`)}
                            </Link>
                        ))}
                    </nav>
                </div>
            </header>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-4xl px-4 py-10">
                    <h1 className="text-brand-green-900 mb-6 text-2xl font-semibold">
                        {title}
                    </h1>

                    {children}
                </div>
            </div>
        </PublicLayout>
    );
}
