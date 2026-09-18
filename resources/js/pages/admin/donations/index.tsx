import { Deferred, router, useForm } from '@inertiajs/react';
import { Gift, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Donation, Option } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    donations: Paginated<Donation>;
    filters: Record<string, string | null>;
    options: { statuses: Option[]; campaigns: string[]; events: Option[] };
    totals?: { received: number; pending: number };
    can: { manage: boolean; record: boolean };
};

export default function DonationsIndex({ donations, totals, can }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.donations.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.donations.title')}
                    </h1>

                    {can.manage && <RecordDonationForm />}
                </div>

                <Deferred
                    data="totals"
                    fallback={<Skeleton className="h-24 w-full rounded-xl" />}
                >
                    <Totals totals={totals} />
                </Deferred>

                {donations.data.length === 0 ? (
                    <EmptyState
                        icon={Gift}
                        title={t('common.states.empty')}
                        description={t('admin.donations.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {donations.data.map((donation) => (
                            <div
                                key={donation.ulid}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="flex items-center gap-2 truncate font-medium">
                                        {donation.is_anonymous
                                            ? t('admin.donations.anonymous')
                                            : donation.donor_name}

                                        {donation.is_anonymous && (
                                            <Badge variant="outline">
                                                {t('admin.donations.anonymous')}
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {donation.campaign ?? ''}
                                        {donation.received_at
                                            ? ` · ${formatDate(donation.received_at)}`
                                            : ''}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium tabular-nums">
                                        {formatCurrency(donation.amount)}
                                    </span>

                                    <Badge
                                        variant={
                                            donation.status === 'received'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {donation.status_label}
                                    </Badge>

                                    {can.record &&
                                        donation.status === 'pending' && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    router.post(
                                                        `/admin/donations/${donation.ulid}/receive`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {t('admin.donations.receive')}
                                            </Button>
                                        )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={donations.meta} />
            </div>
        </AdminLayout>
    );
}

function Totals({
    totals,
}: {
    totals?: { received: number; pending: number };
}) {
    const { t } = useTranslation();

    if (!totals) {
        return null;
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {(
                [
                    [t('admin.donations.total_received'), totals.received],
                    [t('admin.donations.total_pending'), totals.pending],
                ] as Array<[string, number]>
            ).map(([label, value]) => (
                <div key={label} className="bg-card rounded-lg border p-4">
                    <div className="text-muted-foreground text-xs">{label}</div>
                    <div className="mt-1 text-xl font-semibold tabular-nums">
                        {formatCurrency(value)}
                    </div>
                </div>
            ))}
        </div>
    );
}

function RecordDonationForm() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        donor_name: '',
        amount: '',
        campaign: '',
        is_anonymous: false,
        received: true,
        method: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/donations', {
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.donations.record')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="donor_name">
                                {t('admin.donations.donor')}
                            </Label>
                            <Input
                                id="donor_name"
                                value={form.data.donor_name}
                                onChange={(e) =>
                                    form.setData('donor_name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.donor_name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="donation_amount">
                                {t('member.events.amount_due')}
                            </Label>
                            <Input
                                id="donation_amount"
                                type="number"
                                step="0.01"
                                value={form.data.amount}
                                onChange={(e) =>
                                    form.setData('amount', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.amount} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="campaign">
                                {t('admin.donations.campaign')}
                            </Label>
                            <Input
                                id="campaign"
                                value={form.data.campaign}
                                onChange={(e) =>
                                    form.setData('campaign', e.target.value)
                                }
                            />
                            <InputError message={form.errors.campaign} />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-5">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.is_anonymous}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'is_anonymous',
                                        checked === true,
                                    )
                                }
                            />
                            {t('admin.donations.anonymous')}
                        </label>

                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.received}
                                onCheckedChange={(checked) =>
                                    form.setData('received', checked === true)
                                }
                            />
                            {t('admin.donations.received_now')}
                        </label>
                    </div>

                    <p className="text-muted-foreground text-xs">
                        {t('admin.donations.anonymous_hint')}
                    </p>

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                        >
                            {t('common.actions.cancel')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
