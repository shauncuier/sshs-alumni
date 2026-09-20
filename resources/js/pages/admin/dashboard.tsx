import { Deferred, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    Award,
    Bell,
    Briefcase,
    Building,
    CalendarClock,
    FileText,
    Gift,
    GraduationCap,
    HeartHandshake,
    Plus,
    ShieldCheck,
    Sparkles,
    Ticket,
    UserCheck,
    UserPlus,
    Users,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import { AdminCharts, type ChartData } from '@/components/admin/admin-charts';

type Stats = {
    total_members: number;
    verified_members: number;
    pending_registrations: number;
    new_this_month: number;
    active_members: number;
    batches: number;
    events: number;
    event_registrations: number;
    total_donations: number;
    total_sponsors: number;
    total_volunteers: number;
    total_businesses?: number;
    total_jobs?: number;
    total_certificates?: number;
    total_campaigns?: number;
};

type Jubilee = {
    ulid: string;
    title: string | null;
    date_is_tba: boolean;
    starts_at: string | null;
};

type Crm = {
    contacts: number;
    /** An unowned contact is nobody's job, which is how prospects go cold. */
    unassigned: number;
    my_open_tasks: number;
    overdue_tasks: number;
};

type Props = { stats: Stats; jubilee?: Jubilee | null; crm?: Crm | null; charts?: ChartData | null };

const PRIMARY_CARDS: {
    key: 'total_members' | 'verified_members' | 'pending_registrations' | 'new_this_month';
    label: string;
    icon: ComponentType<{ className?: string }>;
    accent: string;
    bgAccent: string;
}[] = [
    {
        key: 'total_members',
        label: 'total_members',
        icon: Users,
        accent: 'text-blue-500 dark:text-blue-400',
        bgAccent: 'bg-blue-500/10 dark:bg-blue-950/40 ring-blue-500/20',
    },
    {
        key: 'verified_members',
        label: 'verified_members',
        icon: UserCheck,
        accent: 'text-emerald-500 dark:text-emerald-400',
        bgAccent: 'bg-emerald-500/10 dark:bg-emerald-950/40 ring-emerald-500/20',
    },
    {
        key: 'pending_registrations',
        label: 'pending_registrations',
        icon: UserPlus,
        accent: 'text-amber-500 dark:text-amber-400',
        bgAccent: 'bg-amber-500/10 dark:bg-amber-950/40 ring-amber-500/20',
    },
    {
        key: 'new_this_month',
        label: 'new_this_month',
        icon: CalendarClock,
        accent: 'text-purple-500 dark:text-purple-400',
        bgAccent: 'bg-purple-500/10 dark:bg-purple-950/40 ring-purple-500/20',
    },
];

export default function AdminDashboard({ stats }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.nav.dashboard')}>
            <div className="space-y-8">
                {/* Executive Command Header */}
                <div className="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-r from-card via-card/90 to-primary/5 p-6 md:p-8 shadow-xs backdrop-blur-md">
                    <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-1.5">
                            <div className="flex items-center gap-2.5">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    <span className="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                    System Active
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    SSHS Alumni Command Center
                                </span>
                            </div>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                                {t('admin.nav.dashboard')}
                            </h1>
                            <p className="text-sm text-muted-foreground max-w-2xl">
                                Executive oversight for membership verifications, institutional giving, communications, and alumni engagement services.
                            </p>
                        </div>

                        {/* Quick Control Bar */}
                        <div className="flex flex-wrap items-center gap-2.5">
                            <Button asChild variant="outline" size="sm" className="gap-1.5 rounded-xl border-border/80 bg-background/80">
                                <Link href="/admin/announcements/create">
                                    <Plus className="size-3.5" />
                                    <span>Announcement</span>
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="sm" className="gap-1.5 rounded-xl border-border/80 bg-background/80">
                                <Link href="/admin/events/create">
                                    <Plus className="size-3.5" />
                                    <span>New Event</span>
                                </Link>
                            </Button>
                            {stats.pending_registrations > 0 && (
                                <Button asChild size="sm" className="gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-xs">
                                    <Link href="/admin/members?status=pending">
                                        <UserPlus className="size-3.5" />
                                        <span>Verify ({stats.pending_registrations})</span>
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Primary Membership KPI Cards */}
                <div>
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                            Membership Growth & Verification
                        </h2>
                        <Link href="/admin/members" className="text-xs font-medium text-primary hover:underline inline-flex items-center gap-1">
                            <span>All Directory</span>
                            <ArrowUpRight className="size-3" />
                        </Link>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {PRIMARY_CARDS.map((card) => (
                            <Card
                                key={card.key}
                                className="group relative overflow-hidden border-border/70 bg-card/70 backdrop-blur-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-border hover:shadow-md"
                            >
                                <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                    <CardTitle className="text-muted-foreground text-sm font-medium">
                                        {t(`admin.dashboard.${card.label}`)}
                                    </CardTitle>
                                    <div className={`flex size-8 items-center justify-center rounded-lg ring-1 ${card.bgAccent}`}>
                                        <card.icon
                                            className={`size-4 shrink-0 ${card.accent}`}
                                            aria-hidden="true"
                                        />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold tracking-tight tabular-nums text-foreground">
                                        {formatNumber(stats[card.key])}
                                    </p>
                                    <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                        <span>
                                            {card.key === 'pending_registrations' && stats.pending_registrations > 0
                                                ? 'Action required'
                                                : 'Real-time database'}
                                        </span>
                                        {card.key === 'pending_registrations' && stats.pending_registrations > 0 && (
                                            <span className="font-semibold text-amber-600 dark:text-amber-400">
                                                Review →
                                            </span>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Secondary Institutional Highlights */}
                <div>
                    <div className="mb-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                            Institutional Giving & Operations
                        </h2>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-amber-500/40 transition-colors">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Total Giving</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20">
                                    <HeartHandshake className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-amber-600 dark:text-amber-400 font-mono">
                                    ৳{stats.total_donations.toLocaleString()}
                                </p>
                                <Link href="/admin/donations" className="text-xs text-muted-foreground hover:text-foreground hover:underline mt-1 inline-block">
                                    Verified contributions & drives →
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-sky-500/40 transition-colors">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Event Passes</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500 ring-1 ring-sky-500/20">
                                    <Ticket className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.event_registrations)}
                                </p>
                                <Link href="/admin/events" className="text-xs text-muted-foreground hover:text-foreground hover:underline mt-1 inline-block">
                                    Across {stats.events} scheduled events →
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-teal-500/40 transition-colors">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Active Cohorts</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-teal-500/10 text-teal-500 ring-1 ring-teal-500/20">
                                    <GraduationCap className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.batches)}
                                </p>
                                <Link href="/admin/batches" className="text-xs text-muted-foreground hover:text-foreground hover:underline mt-1 inline-block">
                                    From SSC 1981 onwards →
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-emerald-500/40 transition-colors">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Volunteers & Staff</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20">
                                    <ShieldCheck className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.total_volunteers)}
                                </p>
                                <Link href="/admin/volunteers" className="text-xs text-muted-foreground hover:text-foreground hover:underline mt-1 inline-block">
                                    Organizing committee members →
                                </Link>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Tertiary Alumni Network & Services Highlights */}
                <div>
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                            Alumni Services & Modern Portals
                        </h2>
                        <span className="text-xs text-muted-foreground">Moderation & Administration</span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-emerald-500/50 transition-all hover:-translate-y-0.5">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Alumni Businesses</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20">
                                    <Building className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.total_businesses ?? 0)}
                                </p>
                                <Link href="/admin/businesses" className="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                                    <span>Manage enterprise listings</span>
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-cyan-500/50 transition-all hover:-translate-y-0.5">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Job Postings</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500 ring-1 ring-cyan-500/20">
                                    <Briefcase className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.total_jobs ?? 0)}
                                </p>
                                <Link href="/admin/jobs" className="text-xs text-cyan-600 dark:text-cyan-400 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                                    <span>Moderate career board</span>
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-purple-500/50 transition-all hover:-translate-y-0.5">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Verifiable Certificates</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-purple-500/10 text-purple-500 ring-1 ring-purple-500/20">
                                    <Award className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.total_certificates ?? 0)}
                                </p>
                                <Link href="/admin/certificates" className="text-xs text-purple-600 dark:text-purple-400 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                                    <span>Issue & verify serials</span>
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 bg-card/70 backdrop-blur-xs shadow-xs hover:border-blue-500/50 transition-all hover:-translate-y-0.5">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">Fundraising Campaigns</CardTitle>
                                <div className="flex size-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 ring-1 ring-blue-500/20">
                                    <Gift className="size-4 shrink-0" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold tabular-nums text-foreground">
                                    {formatNumber(stats.total_campaigns ?? 0)}
                                </p>
                                <Link href="/admin/fundraising" className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                                    <span>Fundraising goals</span>
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* The Jubilee's date status */}
                <Deferred
                    data="jubilee"
                    fallback={<Skeleton className="h-24 w-full rounded-2xl" />}
                >
                    <JubileeStatus />
                </Deferred>

                {/* Analytical Charts */}
                <Deferred
                    data="charts"
                    fallback={
                        <div className="space-y-4">
                            <Skeleton className="h-8 w-48 rounded" />
                            <div className="grid gap-6 lg:grid-cols-2">
                                <Skeleton className="h-72 w-full rounded-2xl" />
                                <Skeleton className="h-72 w-full rounded-2xl" />
                            </div>
                            <div className="grid gap-6 md:grid-cols-3">
                                <Skeleton className="h-60 w-full rounded-2xl" />
                                <Skeleton className="h-60 w-full rounded-2xl" />
                                <Skeleton className="h-60 w-full rounded-2xl" />
                            </div>
                        </div>
                    }
                >
                    <ChartsSection />
                </Deferred>

                {/* CRM Summary */}
                <Deferred
                    data="crm"
                    fallback={<Skeleton className="h-28 w-full rounded-2xl" />}
                >
                    <CrmSummary />
                </Deferred>
            </div>
        </AdminLayout>
    );
}

function ChartsSection() {
    const page = usePage();
    const charts = page.props.charts as ChartData | null | undefined;

    if (!charts) {
        return null;
    }

    return <AdminCharts data={charts} />;
}

function JubileeStatus({ jubilee }: { jubilee?: Jubilee | null }) {
    const { t } = useTranslation();

    if (!jubilee) {
        return null;
    }

    return (
        <Alert
            variant={jubilee.date_is_tba ? 'default' : 'default'}
            className={
                jubilee.date_is_tba
                    ? 'rounded-2xl border-brand-gold-500/50 bg-brand-gold-100/40 dark:bg-amber-950/20'
                    : 'rounded-2xl border-brand-green-600/40 bg-brand-green-100/50 dark:bg-emerald-950/20'
            }
        >
            <AlertTitle className="font-semibold">{jubilee.title}</AlertTitle>
            <AlertDescription className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                <span>
                    {jubilee.date_is_tba
                        ? t('admin.jubilee.date_not_set')
                        : t('admin.jubilee.date_published')}
                </span>
                <Link
                    href="/admin/jubilee"
                    className="text-brand-green-800 dark:text-emerald-400 font-medium underline underline-offset-4"
                >
                    {t('admin.jubilee.announce_date')}
                </Link>
            </AlertDescription>
        </Alert>
    );
}

/**
 * What is outstanding in the CRM.
 *
 * Each figure links to the filter that shows exactly those records — a number
 * you cannot click through to is trivia.
 */
function CrmSummary() {
    const { t } = useTranslation();
    const page = usePage();

    const crm = page.props.crm as Crm | null | undefined;

    if (!crm) {
        return null;
    }

    const cells: Array<{
        label: string;
        value: number;
        href: string;
        alert?: boolean;
    }> = [
        {
            label: t('admin.crm.contacts'),
            value: crm.contacts,
            href: '/admin/crm/contacts',
        },
        {
            label: t('admin.crm.owner_unassigned'),
            value: crm.unassigned,
            href: '/admin/crm/contacts?owner=none',
            alert: crm.unassigned > 0,
        },
        {
            label: t('admin.crm.owner_mine'),
            value: crm.my_open_tasks,
            href: '/admin/crm/tasks?assignee=me',
        },
        {
            label: t('admin.crm.task_overdue'),
            value: crm.overdue_tasks,
            href: '/admin/crm/tasks?overdue=1',
            alert: crm.overdue_tasks > 0,
        },
    ];

    return (
        <Card className="rounded-2xl border-border/80 bg-card/80 backdrop-blur-xs">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                    {t('admin.nav.crm')} Activity & Pipeline
                </CardTitle>
            </CardHeader>

            <CardContent className="grid gap-4 sm:grid-cols-4">
                {cells.map((cell) => (
                    <Link
                        key={cell.label}
                        href={cell.href}
                        className="hover:bg-accent/60 -m-2 rounded-xl p-3 transition-colors"
                    >
                        <p
                            className={
                                cell.alert
                                    ? 'text-destructive text-2xl font-bold tabular-nums'
                                    : 'text-2xl font-bold tabular-nums text-foreground'
                            }
                        >
                            {formatNumber(cell.value)}
                        </p>
                        <p className="text-muted-foreground text-xs mt-0.5">
                            {cell.label}
                        </p>
                    </Link>
                ))}
            </CardContent>
        </Card>
    );
}
