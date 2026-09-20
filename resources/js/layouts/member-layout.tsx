import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Award,
    Briefcase,
    Building,
    CalendarDays,
    ChevronDown,
    CreditCard,
    ExternalLink,
    Gift,
    GraduationCap,
    HeartHandshake,
    IdCard,
    LayoutGrid,
    MessagesSquare,
    Search,
    Shield,
    UserCircle,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/shared/brand-mark';
import { UserMenuContent } from '@/components/user-menu-content';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { User } from '@/types/auth';
import type { NavItem } from '@/types/shared';

type Props = {
    children: ReactNode;
    title?: string;
};

const ICONS: Record<string, LucideIcon> = {
    LayoutGrid,
    UserCircle,
    IdCard,
    Users,
    GraduationCap,
    MessagesSquare,
    CalendarDays,
    CreditCard,
    Gift,
    HeartHandshake,
    Briefcase,
    Building,
    Award,
};

/**
 * The member area shell.
 *
 * Polished glassmorphic navbar with active pill indicators, user identity chip,
 * and responsive horizontal category navigation.
 */
export default function MemberLayout({ children, title }: Props) {
    const { t } = useTranslation();
    const getInitials = useInitials();
    const page = usePage();
    const current = page.url;

    useFlashMessages();

    const user = (page.props.auth as { user?: User } | undefined)?.user;
    // Built server-side from routes that exist, and already narrowed to
    // what an unapproved member may reach.
    const nav = page.props.nav as { member?: NavItem[] } | undefined;
    const items = nav?.member ?? [];

    return (
        <div className="bg-muted/30 min-h-screen">
            <Head title={title ?? t('admin.nav.dashboard')}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <header className="sticky top-0 z-50 border-b border-border/70 bg-background/85 backdrop-blur-xl supports-backdrop-filter:bg-background/75 shadow-xs transition-all">
                {/* Top utility row */}
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/"
                            aria-label={t('public.nav.home')}
                            className="flex items-center gap-2.5 transition-opacity hover:opacity-90"
                        >
                            <BrandMark size="sm" />
                            <span className="hidden sm:inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 ring-1 ring-emerald-500/20">
                                Alumni Portal
                            </span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3">
                        {/* Quick Directory link */}
                        <Link
                            href="/directory"
                            className="hidden md:inline-flex items-center gap-1.5 rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                        >
                            <Search className="size-3.5" />
                            <span>Alumni Directory</span>
                        </Link>

                        {/* Public site link */}
                        <Link
                            href="/"
                            className="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent/60 transition-colors"
                        >
                            <span>Public Site</span>
                            <ExternalLink className="size-3 opacity-70" />
                        </Link>

                        {/* User identity profile trigger */}
                        {user && (
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    className="group flex items-center gap-2 rounded-full border border-border/70 bg-background/80 py-1 pe-2.5 ps-1 text-left text-xs font-medium shadow-2xs hover:border-emerald-500/40 hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 transition-all"
                                    aria-label={user.name}
                                >
                                    <Avatar className="size-7 ring-1 ring-emerald-500/30">
                                        <AvatarFallback className="bg-gradient-to-tr from-emerald-600 to-teal-500 text-[11px] font-bold text-white">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="hidden sm:inline-block max-w-[120px] truncate text-foreground font-semibold">
                                        {user.name}
                                    </span>
                                    <ChevronDown className="size-3.5 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    className="w-56 rounded-xl shadow-lg border-border/70"
                                >
                                    <UserMenuContent user={user} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                </div>

                {/* Horizontal Navigation Pills */}
                <nav
                    className="mx-auto flex max-w-6xl gap-1.5 overflow-x-auto px-4 pb-2.5 pt-0.5 sm:px-6 no-scrollbar"
                    aria-label={t('admin.nav.dashboard')}
                >
                    {items.map((item) => {
                        const active =
                            item.href === '/dashboard'
                                ? current === '/dashboard' || current === '/dashboard/'
                                : current.startsWith(item.href);
                        const Icon = ICONS[item.icon] ?? LayoutGrid;

                        return (
                            <Link
                                key={item.key}
                                href={item.href}
                                className={cn(
                                    'group flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs font-medium transition-all duration-200',
                                    active
                                        ? 'bg-emerald-600 text-white shadow-xs font-semibold dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/40'
                                        : 'text-muted-foreground hover:bg-accent/80 hover:text-foreground',
                                )}
                            >
                                <Icon
                                    className={cn(
                                        'size-3.5 transition-transform duration-200 group-hover:scale-110',
                                        active
                                            ? 'text-white dark:text-emerald-300'
                                            : 'text-muted-foreground group-hover:text-foreground',
                                    )}
                                    aria-hidden="true"
                                />
                                <span>{t(`member.nav.${item.key}`)}</span>
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6 sm:px-6">{children}</main>
        </div>
    );
}
