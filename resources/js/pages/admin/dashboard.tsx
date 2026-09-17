import { Deferred, Link } from '@inertiajs/react';
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

type Props = { stats: Stats; jubilee?: Jubilee | null };

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
    const { t, locale } = useTranslation();

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
                                    {formatNumber(stats[card.key], locale)}
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
                                {formatNumber(stats.batches, locale)}
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
                                {formatNumber(stats.events, locale)}
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
