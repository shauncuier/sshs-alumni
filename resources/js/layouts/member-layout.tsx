import { Head, Link, usePage } from '@inertiajs/react';
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
import type { ComponentType, ReactNode } from 'react';
import { BrandMark } from '@/components/shared/brand-mark';
import { LocaleSwitcher } from '@/components/shared/locale-switcher';
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

type Props = {
    children: ReactNode;
    title?: string;
    /** Approved-only areas are hidden until the committee verifies the member. */
    approved?: boolean;
};

type Item = {
    key: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
    approvedOnly?: boolean;
};

const ITEMS: Item[] = [
    { key: 'dashboard', href: '/dashboard', icon: LayoutGrid },
    { key: 'profile', href: '/my/profile', icon: UserCircle },
    { key: 'card', href: '/my/card', icon: IdCard, approvedOnly: true },
    { key: 'directory', href: '/directory', icon: Users, approvedOnly: true },
    {
        key: 'community',
        href: '/community',
        icon: MessagesSquare,
        approvedOnly: true,
    },
    {
        key: 'batch',
        href: '/my/batch',
        icon: GraduationCap,
        approvedOnly: true,
    },
    { key: 'events', href: '/my/events', icon: CalendarDays },
    { key: 'payments', href: '/my/payments', icon: CreditCard },
    { key: 'donations', href: '/my/donations', icon: Gift },
];

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
export default function MemberLayout({
    children,
    title,
    approved = false,
}: Props) {
    const { t, locale } = useTranslation();
    const getInitials = useInitials();
    const page = usePage();
    const current = page.url;

    useFlashMessages();

    const user = (page.props.auth as { user?: User } | undefined)?.user;
    const items = ITEMS.filter((item) => approved || !item.approvedOnly);

    return (
        <div lang={locale} className="bg-muted/30 min-h-screen">
            <Head title={title ?? t('admin.nav.dashboard')}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <header className="bg-background sticky top-0 z-40 border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
                    <Link href="/" aria-label={t('public.nav.home')}>
                        <BrandMark size="sm" />
                    </Link>

                    <div className="ms-auto flex items-center gap-2">
                        <LocaleSwitcher />

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
                                <item.icon
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                <span lang={locale}>
                                    {t(`member.nav.${item.key}`)}
                                </span>
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div>
    );
}
