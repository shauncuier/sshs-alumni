import { Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Award,
    Briefcase,
    Building,
    CalendarDays,
    CreditCard,
    ExternalLink,
    Gift,
    Globe,
    GraduationCap,
    HeartHandshake,
    IdCard,
    LayoutGrid,
    MessagesSquare,
    Sparkles,
    UserCircle,
    Users,
} from 'lucide-react';
import { NavUser } from '@/components/nav-user';
import { BrandMark } from '@/components/shared/brand-mark';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types/shared';

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

// Logical grouping keys for member items
const GROUP_MAP: Record<string, string> = {
    dashboard: 'General',
    profile: 'General',
    card: 'General',
    directory: 'Alumni Network',
    batch: 'Alumni Network',
    community: 'Alumni Network',
    events: 'Alumni Network',
    donors: 'Member Services',
    jobs: 'Member Services',
    mentorship: 'Member Services',
    businesses: 'Member Services',
    certificates: 'Member Services',
    money: 'Contributions',
};

export function MemberSidebar() {
    const { t } = useTranslation();
    const page = usePage();
    const current = page.url;

    const nav = page.props.nav as { member?: NavItem[] } | undefined;
    const items = nav?.member ?? [];

    // Group items dynamically based on registered items
    const groups: { name: string; items: NavItem[] }[] = [];
    const groupNames = ['General', 'Alumni Network', 'Member Services', 'Contributions'];

    for (const groupName of groupNames) {
        const groupItems = items.filter(
            (item) => (GROUP_MAP[item.key] ?? 'General') === groupName,
        );
        if (groupItems.length > 0) {
            groups.push({ name: groupName, items: groupItems });
        }
    }

    // Any unmapped items go to General
    const mappedKeys = new Set(groups.flatMap((g) => g.items.map((i) => i.key)));
    const unmapped = items.filter((i) => !mappedKeys.has(i.key));
    if (unmapped.length > 0) {
        if (groups.length > 0 && groups[0].name === 'General') {
            groups[0].items.push(...unmapped);
        } else {
            groups.unshift({ name: 'General', items: unmapped });
        }
    }

    return (
        <Sidebar collapsible="icon" variant="inset" className="border-r border-sidebar-border/60">
            <SidebarHeader className="border-b border-sidebar-border/50 py-3.5 px-4">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="hover:bg-transparent">
                            <Link href="/dashboard" className="flex items-center gap-2.5">
                                <BrandMark size="sm" />
                                <div className="flex flex-col gap-0.5 leading-none group-data-[collapsible=icon]:hidden">
                                    <span className="font-bold text-sm text-sidebar-foreground tracking-tight">
                                        SSHS Alumni
                                    </span>
                                    <span className="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        Member Portal
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-2">
                {groups.map((group) => (
                    <SidebarGroup key={group.name} className="py-1.5">
                        <SidebarGroupLabel className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground/70 px-2 group-data-[collapsible=icon]:hidden">
                            {group.name}
                        </SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {group.items.map((item) => {
                                    const active =
                                        item.href === '/dashboard'
                                            ? current === '/dashboard' || current === '/dashboard/'
                                            : current.startsWith(item.href);
                                    const Icon = ICONS[item.icon] ?? LayoutGrid;

                                    return (
                                        <SidebarMenuItem key={item.key}>
                                            <SidebarMenuButton
                                                asChild
                                                isActive={active}
                                                tooltip={t(`member.nav.${item.key}`)}
                                                className={cn(
                                                    'rounded-xl text-xs font-medium transition-all duration-150 py-2.5 px-3',
                                                    active
                                                        ? 'bg-emerald-600 text-white font-semibold shadow-xs hover:bg-emerald-600 hover:text-white dark:bg-emerald-600 dark:text-white'
                                                        : 'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                                )}
                                            >
                                                <Link href={item.href} className="flex items-center gap-2.5">
                                                    <Icon
                                                        className={cn(
                                                            'size-4 shrink-0 transition-transform group-hover:scale-110',
                                                            active ? 'text-white' : 'text-muted-foreground',
                                                        )}
                                                    />
                                                    <span className="truncate">{t(`member.nav.${item.key}`)}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/50 p-2 space-y-1">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild size="sm" className="text-xs text-muted-foreground hover:text-foreground">
                            <Link href="/">
                                <Globe className="size-3.5 text-muted-foreground" />
                                <span>Public Website</span>
                                <ExternalLink className="ml-auto size-3 opacity-60" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
