import { Link, usePage } from '@inertiajs/react';
import {
    Award,
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
    UserSquare,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { NavUser } from '@/components/nav-user';
import { BrandMark } from '@/components/shared/brand-mark';
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
import { useTranslation } from '@/hooks/use-translation';
import type { AdminNavGroup } from '@/types/shared';

/**
 * Icons are named by the server and resolved here — the nav itself is built
 * from routes that exist, so an item only appears once its phase has landed.
 *
 * @see app/Support/Navigation.php
 */
const ICONS: Record<string, LucideIcon> = {
    Award,
    CalendarDays,
    Contact,
    FileText,
    Gift,
    GraduationCap,
    HandHeart,
    Handshake,
    Image,
    Megaphone,
    MessagesSquare,
    Newspaper,
    ScrollText,
    Settings,
    ShieldCheck,
    Sparkles,
    UserSquare,
    Users,
    Wallet,
};

export function AdminSidebar() {
    const { t } = useTranslation();
    const page = usePage();
    const current = page.url;

    const nav = page.props.nav as { admin?: AdminNavGroup[] } | undefined;
    const groups = nav?.admin ?? [];

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
                                    <span>{t('admin.nav.dashboard')}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>

                {groups.map((group) => (
                    <SidebarGroup key={group.label}>
                        <SidebarGroupLabel>
                            {t(`admin.groups.${group.label}`)}
                        </SidebarGroupLabel>

                        <SidebarMenu>
                            {group.items.map((item) => {
                                const Icon = ICONS[item.icon] ?? LayoutGrid;

                                return (
                                    <SidebarMenuItem key={item.key}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={current.startsWith(
                                                item.href,
                                            )}
                                            tooltip={t(`admin.nav.${item.key}`)}
                                        >
                                            <Link href={item.href} prefetch>
                                                <Icon aria-hidden="true" />
                                                <span>
                                                    {t(`admin.nav.${item.key}`)}
                                                </span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
