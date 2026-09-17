import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { useTranslation } from '@/hooks/use-translation';
import type { BreadcrumbItem } from '@/types';

type Props = {
    children: ReactNode;
    title?: string;
    breadcrumbs?: BreadcrumbItem[];
};

/**
 * The admin shell.
 *
 * Reuses the starter kit's AppShell, AppContent and AppSidebarHeader rather
 * than reimplementing them; only the sidebar is ours, because it is
 * permission-filtered.
 *
 * Deliberately quiet: the neutral palette with green as an accent. A CRM that
 * shouts at its operator all day is a worse CRM.
 */
export default function AdminLayout({
    children,
    title,
    breadcrumbs = [],
}: Props) {
    const { t, locale } = useTranslation();

    useFlashMessages();

    return (
        <div lang={locale}>
            <Head title={title ?? t('admin.nav.dashboard')}>
                {/* Admin pages are never indexed. */}
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <AppShell variant="sidebar">
                <AdminSidebar />

                <AppContent
                    variant="sidebar"
                    className="min-w-0 overflow-x-clip"
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <div className="p-4 md:p-6">{children}</div>
                </AppContent>
            </AppShell>
        </div>
    );
}
