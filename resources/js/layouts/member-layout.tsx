import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { MemberSidebar } from '@/components/member/member-sidebar';
import { MemberSidebarHeader } from '@/components/member/member-sidebar-header';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { useTranslation } from '@/hooks/use-translation';
import type { BreadcrumbItem } from '@/types';

type Props = {
    children: ReactNode;
    title?: string;
    breadcrumbs?: BreadcrumbItem[];
};

/**
 * The member area shell.
 *
 * Configured as a left-hand navigation sidebar using AppShell, providing
 * desktop collapsible sidebar and mobile slide-out sheet drawer.
 */
export default function MemberLayout({
    children,
    title,
    breadcrumbs = [],
}: Props) {
    const { t } = useTranslation();

    useFlashMessages();

    return (
        <div>
            <Head title={title ?? t('admin.nav.dashboard')}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <AppShell variant="sidebar">
                <MemberSidebar />

                <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                    <MemberSidebarHeader title={title} breadcrumbs={breadcrumbs} />
                    <main className="p-4 md:p-6 max-w-7xl mx-auto w-full">{children}</main>
                </AppContent>
            </AppShell>
        </div>
    );
}
