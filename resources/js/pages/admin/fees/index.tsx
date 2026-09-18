import { Deferred, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Receipt, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Fee, Option } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    fees: Paginated<Fee>;
    filters: Record<string, string | null>;
    options: { statuses: Option[]; periods: string[] };
    totals?: { collected: number; outstanding: number; waived: number };
    can: { manage: boolean };
};

export default function FeesIndex({
    fees,
    filters,
    options,
    totals,
    can,
}: Props) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/fees',
    );

    return (
        <AdminLayout title={t('admin.fees.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.fees.title')}
                    </h1>

                    {can.manage && <GenerateForm />}
                </div>

                <Deferred
                    data="totals"
                    fallback={<Skeleton className="h-24 w-full rounded-xl" />}
                >
                    <Totals totals={totals} />
                </Deferred>

                <div className="flex flex-wrap gap-2">
                    <div className="relative min-w-60 flex-1">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder={t('admin.members.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <Select
                        value={filters.status ?? '__all'}
                        onValueChange={(value) =>
                            router.get(
                                '/admin/fees',
                                {
                                    ...filters,
                                    status:
                                        value === '__all' ? undefined : value,
                                },
                                { preserveState: true, replace: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-auto min-w-40">
                            <SelectValue
                                placeholder={t('common.labels.status')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">
                                {t('common.labels.status')}
                            </SelectItem>
                            {options.statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {Object.values(filters).some(Boolean) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/fees')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {fees.data.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title={t('common.states.no_results')}
                        description={t('admin.fees.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {fees.data.map((fee) => (
                            <FeeRow key={fee.id} fee={fee} can={can} />
                        ))}
                    </div>
                )}

                <Pagination meta={fees.meta} />
            </div>
        </AdminLayout>
    );
}

function Totals({
    totals,
}: {
    totals?: { collected: number; outstanding: number; waived: number };
}) {
    const { t } = useTranslation();

    if (!totals) {
        return null;
    }

    const cells: Array<[string, number]> = [
        [t('admin.fees.collected'), totals.collected],
        [t('admin.fees.outstanding'), totals.outstanding],
        // Shown separately because it is NOT income — no money was received.
        [t('admin.fees.waived_total'), totals.waived],
    ];

    return (
        <div className="grid gap-3 sm:grid-cols-3">
            {cells.map(([label, value]) => (
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

function FeeRow({ fee, can }: { fee: Fee; can: { manage: boolean } }) {
    const { t } = useTranslation();
    const [waiving, setWaiving] = useState(false);

    const waive = useForm({ reason: '' });

    return (
        <div className="space-y-3 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">
                        {fee.member.full_name}
                    </p>
                    <p className="text-muted-foreground truncate text-sm">
                        {fee.period_label}
                        {fee.due_at ? ` · ${formatDate(fee.due_at)}` : ''}
                        {fee.waived_reason ? ` · ${fee.waived_reason}` : ''}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium tabular-nums">
                        {formatCurrency(fee.amount)}
                    </span>

                    {fee.is_overdue && (
                        <Badge variant="destructive">
                            {t('admin.fees.overdue')}
                        </Badge>
                    )}

                    <Badge
                        variant={
                            fee.status === 'paid'
                                ? 'default'
                                : fee.status === 'waived'
                                  ? 'outline'
                                  : 'secondary'
                        }
                    >
                        {fee.status_label}
                    </Badge>

                    {can.manage && fee.status === 'pending' && (
                        <>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    router.post(
                                        `/admin/fees/${fee.id}/pay`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <BadgeCheck
                                    className="me-1 size-3.5"
                                    aria-hidden="true"
                                />
                                {t('admin.fees.pay')}
                            </Button>

                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => setWaiving((on) => !on)}
                            >
                                {t('admin.fees.waive')}
                            </Button>
                        </>
                    )}
                </div>
            </div>

            {waiving && (
                <form
                    onSubmit={(e: FormEvent) => {
                        e.preventDefault();
                        waive.post(`/admin/fees/${fee.id}/waive`, {
                            preserveScroll: true,
                            onSuccess: () => setWaiving(false),
                        });
                    }}
                    className="bg-muted/40 space-y-2 rounded-md p-3"
                >
                    <p className="text-muted-foreground text-xs">
                        {t('admin.fees.waive_hint')}
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Input
                            value={waive.data.reason}
                            placeholder={t('admin.fees.waive_reason')}
                            onChange={(e) =>
                                waive.setData('reason', e.target.value)
                            }
                            className="min-w-52 flex-1"
                            required
                        />
                        <Button
                            type="submit"
                            size="sm"
                            disabled={waive.processing}
                        >
                            {t('admin.fees.waive')}
                        </Button>
                    </div>
                    <InputError message={waive.errors.reason} />
                </form>
            )}
        </div>
    );
}

function GenerateForm() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({ period_label: '', amount: '', due_at: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/fees/generate', {
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                {t('admin.fees.generate')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <p className="text-muted-foreground text-sm">
                        {t('admin.fees.generate_hint')}
                    </p>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="period_label">
                                {t('admin.fees.period')}
                            </Label>
                            <Input
                                id="period_label"
                                value={form.data.period_label}
                                placeholder={t('admin.fees.period_hint')}
                                onChange={(e) =>
                                    form.setData('period_label', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.period_label} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="fee_amount">
                                {t('member.events.amount_due')}
                            </Label>
                            <Input
                                id="fee_amount"
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
                            <Label htmlFor="due_at">
                                {t('admin.fees.due')}
                            </Label>
                            <Input
                                id="due_at"
                                type="date"
                                value={form.data.due_at}
                                onChange={(e) =>
                                    form.setData('due_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.due_at} />
                        </div>
                    </div>

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
