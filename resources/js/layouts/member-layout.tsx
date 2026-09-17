import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    CalendarDays,
    CreditCard,
    Gift,
    GraduationCap,
    IdCard,
    LayoutGrid,
    MessagesSquare,
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
};

/**
 * The member area shell.
 *
 * A horizontal nav rather than the admin sidebar: members have nine
 * destinations, not twenty-two, and most of them arrive on a phone.
 *
 * Approved-only links are hidden while an application is still under review —
 * the middleware would redirect anyway, and offering a link that bounces is
 * worse than not offering it.
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

            <header className="bg-background sticky top-0 z-40 border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
                    <Link href="/" aria-label={t('public.nav.home')}>
                        <BrandMark size="sm" />
                    </Link>

                    <div className="ms-auto flex items-center gap-2">
                        {user && (
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    className="rounded-full focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    aria-label={user.name}
                                >
                                    <Avatar className="size-9">
                                        <AvatarFallback>
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    className="w-56"
                                >
                                    <UserMenuContent user={user} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                </div>

                <nav
                    className="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 pb-2"
                    aria-label={t('admin.nav.dashboard')}
                >
                    {items.map((item) => {
                        const active = current.startsWith(item.href);
                        const Icon = ICONS[item.icon] ?? LayoutGrid;

                        return (
                            <Link
                                key={item.key}
                                href={item.href}
                                className={cn(
                                    'flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-brand-green-100 text-brand-green-800 dark:bg-sidebar-accent dark:text-foreground'
                                        : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                                )}
                            >
                                <Icon className="size-4" aria-hidden="true" />
                                <span>{t(`member.nav.${item.key}`)}</span>
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div>
    );
}
