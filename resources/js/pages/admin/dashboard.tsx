import { Deferred, Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    GraduationCap,
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

type Stats = {
    total_members: number;
    verified_members: number;
    pending_registrations: number;
    new_this_month: number;
    batches: number;
    events: number;
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

type Props = { stats: Stats; jubilee?: Jubilee | null; crm?: Crm | null };

const CARDS: {
    key: keyof Stats;
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
                <h1 className="text-2xl font-semibold">
                    {t('admin.nav.dashboard')}
                </h1>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {CARDS.map((card) => (
                        <Card key={card.key}>
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

                {/* The Jubilee's date status, stated plainly — the committee
                    should never have to guess whether the public site is
                    showing a date. Deferred because it is a second query. */}
                <Deferred
                    data="jubilee"
                    fallback={<Skeleton className="h-28 w-full rounded-xl" />}
                >
                    <JubileeStatus />
                </Deferred>

                {/* The CRM's own numbers. Absent entirely for anybody without
                    `crm.view` — a widget rendering zeroes at someone who
                    cannot open the section is clutter. */}
                <Deferred
                    data="crm"
                    fallback={<Skeleton className="h-28 w-full rounded-xl" />}
                >
                    <CrmSummary />
                </Deferred>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 pb-2">
                            <GraduationCap
                                className="text-muted-foreground size-4"
                                aria-hidden="true"
                            />
                            <CardTitle className="text-sm font-medium">
                                {t('admin.nav.batches')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.batches)}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 pb-2">
                            <CalendarClock
                                className="text-muted-foreground size-4"
                                aria-hidden="true"
                            />
                            <CardTitle className="text-sm font-medium">
                                {t('admin.nav.events')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {formatNumber(stats.events)}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
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
