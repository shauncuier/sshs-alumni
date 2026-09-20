import { Link, usePage } from '@inertiajs/react';
import { ExternalLink, Eye, Search } from 'lucide-react';
import { useState, useEffect } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { GlobalSearchModal } from '@/components/admin/global-search-modal';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    title?: string;
    breadcrumbs?: BreadcrumbItemType[];
};

const TITLE_MAP: Record<string, string> = {
    admin: 'Command Center',
    dashboard: 'Dashboard',
    events: 'Events & Reunions',
    fundraising: 'Fundraising Campaigns',
    businesses: 'Alumni Businesses',
    jobs: 'Career & Job Board',
    certificates: 'Digital Certificates',
    members: 'Member Directory',
    batches: 'Batch Cohorts',
    payments: 'Payments',
    donations: 'Donations & Giving',
    sponsors: 'Sponsors',
    volunteers: 'Volunteers',
    announcements: 'Announcements',
    news: 'News Articles',
    stories: 'Alumni Stories',
    gallery: 'Photo Gallery',
    pages: 'Static Pages',
    faqs: 'Frequently Asked Questions',
    history: 'Milestones & History',
    templates: 'Message Templates',
    users: 'Staff & Users',
    roles: 'Roles & Permissions',
    settings: 'System Settings',
    audit: 'Audit Logs',
    contacts: 'CRM Contacts',
    tasks: 'CRM Tasks',
    reports: 'Reports & Analytics',
    fees: 'Membership Fees',
    checkin: 'Event Check-in',
    registrations: 'Event Registrations',
    directory: 'Alumni Directory',
    donors: 'Blood Donors Directory',
    mentorship: 'Mentorship Network',
    community: 'Community Feed',
    card: 'Membership Card',
    profile: 'My Profile',
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

export function AppSidebarHeader({
    title,
    breadcrumbs = [],
}: Props) {
    const [searchOpen, setSearchOpen] = useState(false);
    const page = usePage();
    const resolvedTitle = resolveHeaderTitle(page.url, title);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((prev) => !prev);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sidebar-border/60 bg-background/85 px-4 md:px-6 backdrop-blur-xl supports-backdrop-filter:bg-background/75 shadow-2xs transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14">
            <div className="flex items-center gap-3 min-w-0">
                <SidebarTrigger className="-ml-1 text-muted-foreground hover:text-foreground rounded-lg p-1.5 hover:bg-accent transition-colors shrink-0" />
                <div className="h-4 w-px bg-border/80 hidden sm:block shrink-0" />

                {breadcrumbs.length > 0 ? (
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                ) : (
                    <span className="text-sm font-semibold text-foreground truncate max-w-[280px] sm:max-w-none">
                        {resolvedTitle}
                    </span>
                )}
            </div>

            <div className="flex items-center gap-2 sm:gap-3 shrink-0">
                {/* Global Search Shortcut */}
                <button
                    type="button"
                    onClick={() => setSearchOpen(true)}
                    className="group inline-flex items-center gap-2 rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5 text-xs text-muted-foreground hover:border-emerald-500/40 hover:bg-muted/70 hover:text-foreground transition-all shadow-2xs cursor-pointer"
                >
                    <Search className="size-3.5 text-muted-foreground group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" />
                    <span className="hidden sm:inline">Search platform...</span>
                    <kbd className="hidden sm:inline-flex items-center gap-0.5 rounded-md border border-border/80 bg-background/80 px-1.5 py-0.5 text-[10px] font-mono font-medium text-muted-foreground shadow-2xs">
                        ⌘K
                    </kbd>
                </button>

                {/* View Member Portal */}
                <Link
                    href="/dashboard"
                    className="hidden lg:inline-flex items-center gap-1.5 rounded-xl border border-border/70 bg-background/60 px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                    title="Switch to Member View"
                >
                    <Eye className="size-3.5" />
                    <span>Member View</span>
                </Link>

                {/* View Public Site */}
                <Link
                    href="/"
                    className="inline-flex items-center gap-1.5 rounded-xl border border-border/70 bg-background/60 px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                    title="Open Public Website"
                >
                    <span className="hidden sm:inline">Live Site</span>
                    <ExternalLink className="size-3 text-muted-foreground" />
                </Link>
            </div>

            <GlobalSearchModal isOpen={searchOpen} onClose={() => setSearchOpen(false)} />
        </header>
    );
}
