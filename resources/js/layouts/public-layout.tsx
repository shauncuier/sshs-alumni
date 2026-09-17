import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SiteFooter } from '@/components/public/site-footer';
import { SiteHeader } from '@/components/public/site-header';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    children: ReactNode;
    title?: string;
    /** Meta description for this page; falls back to the site default. */
    description?: string;
    /** Public pages are indexed; set false for anything that should not be. */
    indexable?: boolean;
};

/**
 * The public site shell.
 *
 * Light-only by design. The gold-on-green identity does not survive inversion,
 * and a public alumni site with a dark-mode toggle reads as a developer tool —
 * dark mode belongs to the member and admin surfaces.
 *
 * @see docs/07-branding-ui.md section 2
 */
export default function PublicLayout({
    children,
    title,
    description,
    indexable = true,
}: Props) {
    const { t } = useTranslation();

    useFlashMessages();

    // app.tsx already appends the application name, so this is the page title
    // alone — otherwise it reads "Page — Association - SSHS Alumni".
    const pageTitle = title ?? t('public.hero.organisation');

    // `theme-light` pins the public site to light tokens regardless of the
    // visitor's system preference.
    return (
        <div className="theme-light flex min-h-screen flex-col">
            <Head title={pageTitle}>
                {description && (
                    <meta name="description" content={description} />
                )}
                {!indexable && <meta name="robots" content="noindex" />}
                <meta property="og:title" content={pageTitle} />
                {description && (
                    <meta property="og:description" content={description} />
                )}
                <meta property="og:type" content="website" />
                <meta property="og:locale" content="en_GB" />
            </Head>

            {/* Keyboard users should not have to tab through the whole menu
                to reach the page. */}
            <a
                href="#main"
                className="bg-brand-green-800 sr-only rounded-md px-4 py-2 text-white focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-[100]"
            >
                {t('common.actions.next')}
            </a>

            <SiteHeader />

            <main id="main" className="flex-1">
                {children}
            </main>

            <SiteFooter />
        </div>
    );
}
