import { Link, usePage } from '@inertiajs/react';
import { ExternalLink, Search } from 'lucide-react';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type Props = {
    title?: string;
    breadcrumbs?: BreadcrumbItem[];
};

const TITLE_MAP: Record<string, string> = {
    dashboard: 'Dashboard',
    profile: 'My Profile',
    card: 'Membership Card',
    directory: 'Alumni Directory',
    batch: 'My Batch Roster',
    batches: 'Batch Cohorts',
    community: 'Community Feed',
    events: 'Events & Reunions',
    donors: 'Blood Donors Directory',
    jobs: 'Career & Job Board',
    mentorship: 'Mentorship Network',
    businesses: 'Alumni Business Directory',
    certificates: 'Digital Certificates',
    donations: 'My Donations',
    payments: 'Payment History',
    notifications: 'Notifications',
};

function resolveHeaderTitle(url: string, explicitTitle?: string): string {
    if (explicitTitle && explicitTitle.trim() !== '') {
        return explicitTitle;
    }

    const cleanPath = url.split('?')[0].replace(/^\/|\/$/g, '');
    if (!cleanPath) return 'Dashboard';

    const segments = cleanPath.split('/');
    const last = segments[segments.length - 1];

    if (TITLE_MAP[last]) {
        return TITLE_MAP[last];
    }

    return last
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

export function MemberSidebarHeader({ title, breadcrumbs = [] }: Props) {
    const page = usePage();
    const resolvedTitle = resolveHeaderTitle(page.url, title);

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sidebar-border/60 bg-background/85 px-4 md:px-6 backdrop-blur-xl supports-backdrop-filter:bg-background/75 shadow-2xs transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14">
            <div className="flex items-center gap-3 min-w-0">
                <SidebarTrigger className="-ml-1 text-muted-foreground hover:text-foreground rounded-lg p-1.5 hover:bg-accent transition-colors shrink-0" />
                <div className="h-4 w-px bg-border/80 hidden sm:block shrink-0" />
                <span className="text-sm font-semibold text-foreground truncate max-w-[280px] sm:max-w-none">
                    {resolvedTitle}
                </span>
            </div>

            <div className="flex items-center gap-2 sm:gap-3 shrink-0">
                {/* Search directory shortcut */}
                <Link
                    href="/directory"
                    className="inline-flex items-center gap-2 rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5 text-xs text-muted-foreground hover:border-emerald-500/40 hover:bg-muted/70 hover:text-foreground transition-all shadow-2xs"
                >
                    <Search className="size-3.5 text-muted-foreground" />
                    <span className="hidden sm:inline">Find Alumni...</span>
                </Link>

                {/* Switch to Public Site */}
                <Link
                    href="/"
                    className="inline-flex items-center gap-1.5 rounded-xl border border-border/70 bg-background/60 px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                    title="SSHS Public Website"
                >
                    <span className="hidden sm:inline">Public Site</span>
                    <ExternalLink className="size-3 text-muted-foreground" />
                </Link>
            </div>
        </header>
    );
}
