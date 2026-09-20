import { Deferred, Link, usePage } from '@inertiajs/react';
import {
    Award,
    Briefcase,
    Building,
    CalendarClock,
    Gift,
    GraduationCap,
    HeartHandshake,
    ShieldCheck,
    Ticket,
    UserCheck,
    UserPlus,
    Users,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
}[] = [
    { key: 'total_members', label: 'total_members', icon: Users },
    { key: 'verified_members', label: 'verified_members', icon: UserCheck },
    {
        key: 'pending_registrations',
        label: 'pending_registrations',
        icon: UserPlus,
    },
    { key: 'new_this_month', label: 'new_this_month', icon: CalendarClock },
];

export default function AdminDashboard({ stats }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.nav.dashboard')}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        {t('admin.nav.dashboard')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Sabuj Shikshayatan Alumni Association administration and institutional command center.
                    </p>
                </div>

                {/* Primary Membership KPI Cards */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {PRIMARY_CARDS.map((card) => (
                        <Card key={card.key} className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                                <CardTitle className="text-muted-foreground text-sm font-medium">
                                    {t(`admin.dashboard.${card.label}`)}
                                </CardTitle>
                                <card.icon
                                    className="text-muted-foreground size-4 shrink-0"
                                    aria-hidden="true"
                                />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-semibold tabular-nums">
                                    {formatNumber(stats[card.key])}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Secondary Institutional Highlights */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Total Giving</CardTitle>
                            <HeartHandshake className="text-amber-400 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums text-amber-500 font-mono">
                                ৳{stats.total_donations.toLocaleString()}
                            </p>
                            <p className="text-xs text-muted-foreground mt-0.5">Verified contributions</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Event Passes</CardTitle>
                            <Ticket className="text-sky-400 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.event_registrations)}
                            </p>
                            <p className="text-xs text-muted-foreground mt-0.5">Across {stats.events} events</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Active Cohorts</CardTitle>
                            <GraduationCap className="text-teal-400 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.batches)}
                            </p>
                            <p className="text-xs text-muted-foreground mt-0.5">From SSC 1981 onwards</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Volunteers & Staff</CardTitle>
                            <ShieldCheck className="text-emerald-400 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.total_volunteers)}
                            </p>
                            <p className="text-xs text-muted-foreground mt-0.5">Active team members</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tertiary Alumni Network & Services Highlights */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs hover:border-emerald-500/50 transition-colors">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Alumni Businesses</CardTitle>
                            <Building className="text-emerald-500 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.total_businesses ?? 0)}
                            </p>
                            <Link href="/admin/businesses" className="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium mt-0.5 inline-block">
                                Manage listings →
                            </Link>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs hover:border-cyan-500/50 transition-colors">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Job Postings</CardTitle>
                            <Briefcase className="text-cyan-500 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.total_jobs ?? 0)}
                            </p>
                            <Link href="/admin/jobs" className="text-xs text-cyan-600 dark:text-cyan-400 hover:underline font-medium mt-0.5 inline-block">
                                Review board →
                            </Link>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs hover:border-purple-500/50 transition-colors">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Verifiable Certificates</CardTitle>
                            <Award className="text-purple-500 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.total_certificates ?? 0)}
                            </p>
                            <Link href="/admin/certificates" className="text-xs text-purple-600 dark:text-purple-400 hover:underline font-medium mt-0.5 inline-block">
                                Issue & verify →
                            </Link>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card/60 backdrop-blur-sm shadow-xs hover:border-blue-500/50 transition-colors">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">Fundraising Campaigns</CardTitle>
                            <Gift className="text-blue-500 size-4 shrink-0" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.total_campaigns ?? 0)}
                            </p>
                            <Link href="/admin/fundraising" className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium mt-0.5 inline-block">
                                Manage campaigns →
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                {/* The Jubilee's date status */}
                <Deferred
                    data="jubilee"
                    fallback={<Skeleton className="h-24 w-full rounded-xl" />}
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
                                <Skeleton className="h-72 w-full rounded-xl" />
                                <Skeleton className="h-72 w-full rounded-xl" />
                            </div>
                            <div className="grid gap-6 md:grid-cols-3">
                                <Skeleton className="h-60 w-full rounded-xl" />
                                <Skeleton className="h-60 w-full rounded-xl" />
                                <Skeleton className="h-60 w-full rounded-xl" />
                            </div>
                        </div>
                    }
                >
                    <ChartsSection />
                </Deferred>

                {/* CRM Summary */}
                <Deferred
                    data="crm"
                    fallback={<Skeleton className="h-28 w-full rounded-xl" />}
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
                    ? 'border-brand-gold-500/50 bg-brand-gold-100/40'
                    : 'border-brand-green-600/40 bg-brand-green-100/50'
            }
        >
            <AlertTitle>{jubilee.title}</AlertTitle>
            <AlertDescription className="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span>
                    {jubilee.date_is_tba
                        ? t('admin.jubilee.date_not_set')
                        : t('admin.jubilee.date_published')}
                </span>
                <Link
                    href="/admin/jubilee"
                    className="text-brand-green-800 font-medium underline underline-offset-4"
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
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium">
                    {t('admin.nav.crm')}
                </CardTitle>
            </CardHeader>

            <CardContent className="grid gap-4 sm:grid-cols-4">
                {cells.map((cell) => (
                    <Link
                        key={cell.label}
                        href={cell.href}
                        className="hover:bg-accent -m-2 rounded-md p-2"
                    >
                        <p
                            className={
                                cell.alert
                                    ? 'text-destructive text-2xl font-semibold tabular-nums'
                                    : 'text-2xl font-semibold tabular-nums'
                            }
                        >
                            {formatNumber(cell.value)}
                        </p>
                        <p className="text-muted-foreground text-xs">
                            {cell.label}
                        </p>
                    </Link>
                ))}
            </CardContent>
        </Card>
    );
}
