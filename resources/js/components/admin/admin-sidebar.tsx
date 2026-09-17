import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BadgeCheck,
    CalendarDays,
    Contact,
    FileText,
    Gift,
    GraduationCap,
    HandHeart,
    Handshake,
    Image,
    LayoutGrid,
    Megaphone,
    MessagesSquare,
    Newspaper,
    ScrollText,
    Settings,
    ShieldCheck,
    Sparkles,
    Users,
    UserSquare,
    Wallet,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { BrandMark } from '@/components/shared/brand-mark';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/use-permission';
import { useTranslation } from '@/hooks/use-translation';

type Item = {
    key: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
    /** Hidden unless the user holds this permission. */
    permission: string;
};

type Group = { label: string; items: Item[] };

/**
 * The admin sections from the specification, grouped so a 22-item flat list
 * does not become unnavigable.
 *
 * Every item declares the permission that reveals it. This HIDES UI only —
 * each route is enforced server-side by `can:` middleware and a policy, so a
 * user who reaches a hidden URL still gets a 403.
 *
 * @see docs/04-roles-permissions.md section 6
 */
const GROUPS: Group[] = [
    {
        label: 'people',
        items: [
            {
                key: 'members',
                href: '/admin/members',
                icon: Users,
                permission: 'members.view',
            },
            {
                key: 'verification',
                href: '/admin/members?status=pending',
                icon: BadgeCheck,
                permission: 'members.verify',
            },
            {
                key: 'crm',
                href: '/admin/crm/contacts',
                icon: Contact,
                permission: 'crm.view',
            },
            {
                key: 'batches',
                href: '/admin/batches',
                icon: GraduationCap,
                permission: 'batches.view',
            },
        ],
    },
    {
        label: 'events',
        items: [
            {
                key: 'events',
                href: '/admin/events',
                icon: CalendarDays,
                permission: 'events.view',
            },
            {
                key: 'jubilee',
                href: '/admin/jubilee',
                icon: Sparkles,
                permission: 'events.view',
            },
            {
                key: 'volunteers',
                href: '/admin/volunteers',
                icon: HandHeart,
                permission: 'volunteers.view',
            },
            {
                key: 'committees',
                href: '/admin/committees',
                icon: UserSquare,
                permission: 'committees.view',
            },
        ],
    },
    {
        label: 'money',
        items: [
            {
                key: 'payments',
                href: '/admin/payments',
                icon: Wallet,
                permission: 'payments.view',
            },
            {
                key: 'donations',
                href: '/admin/donations',
                icon: Gift,
                permission: 'donations.view',
            },
            {
                key: 'sponsors',
                href: '/admin/sponsors',
                icon: Handshake,
                permission: 'sponsors.view',
            },
        ],
    },
    {
        label: 'content',
        items: [
            {
                key: 'news',
                href: '/admin/news',
                icon: Newspaper,
                permission: 'content.manage',
            },
            {
                key: 'announcements',
                href: '/admin/announcements',
                icon: Megaphone,
                permission: 'content.manage',
            },
            {
                key: 'gallery',
                href: '/admin/gallery',
                icon: Image,
                permission: 'content.manage',
            },
            {
                key: 'pages',
                href: '/admin/pages',
                icon: FileText,
                permission: 'content.manage',
            },
            {
                key: 'history',
                href: '/admin/school-history',
                icon: ScrollText,
                permission: 'content.manage',
            },
            {
                key: 'community',
                href: '/admin/community/posts',
                icon: MessagesSquare,
                permission: 'community.moderate',
            },
        ],
    },
    {
        label: 'system',
        items: [
            {
                key: 'reports',
                href: '/admin/reports',
                icon: Award,
                permission: 'reports.view',
            },
            {
                key: 'users',
                href: '/admin/users',
                icon: Users,
                permission: 'users.manage',
            },
            {
                key: 'roles',
                href: '/admin/roles',
                icon: ShieldCheck,
                permission: 'roles.manage',
            },
            {
                key: 'audit',
                href: '/admin/audit-logs',
                icon: ScrollText,
                permission: 'audit.view',
            },
            {
                key: 'settings',
                href: '/admin/settings/organization',
                icon: Settings,
                permission: 'settings.manage',
            },
        ],
    },
];

export function AdminSidebar() {
    const { t, locale } = useTranslation();
    const { can } = usePermission();
    const current = usePage().url;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <BrandMark size="sm" />
                                <span className="truncate font-semibold group-data-[collapsible=icon]:hidden">
                                    {t('admin.nav.dashboard')}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                isActive={current === '/admin'}
                                tooltip={t('admin.nav.dashboard')}
                            >
                                <Link href="/admin" prefetch>
                                    <LayoutGrid aria-hidden="true" />
                                    <span lang={locale}>
                                        {t('admin.nav.dashboard')}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>

                {GROUPS.map((group) => {
                    const visible = group.items.filter((item) =>
                        can(item.permission),
                    );

                    // An empty group renders nothing rather than an orphan
                    // heading.
                    if (visible.length === 0) {
                        return null;
                    }

                    return (
                        <SidebarGroup key={group.label}>
                            <SidebarGroupLabel lang={locale}>
                                {t(`admin.groups.${group.label}`)}
                            </SidebarGroupLabel>

                            <SidebarMenu>
                                {visible.map((item) => (
                                    <SidebarMenuItem key={item.key}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={current.startsWith(
                                                item.href.split('?')[0],
                                            )}
                                            tooltip={t(`admin.nav.${item.key}`)}
                                        >
                                            <Link href={item.href} prefetch>
                                                <item.icon aria-hidden="true" />
                                                <span lang={locale}>
                                                    {t(`admin.nav.${item.key}`)}
                                                </span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
