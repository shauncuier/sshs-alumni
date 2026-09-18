import { Deferred, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, Wallet, X } from 'lucide-react';
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
import type { LedgerTotals, Option, Payment } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    payments: Paginated<Payment>;
    filters: Record<string, string | null>;
    options: { statuses: Option[] };
    totals?: LedgerTotals;
    can: { record: boolean };
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    paid: 'default',
    pending: 'secondary',
    refunded: 'outline',
    failed: 'destructive',
    cancelled: 'outline',
};

/**
 * The ledger.
 *
 * Every kind of money in one list, so no two reports can disagree about
 * income. There is no edit and no delete here, deliberately — a mistake is
 * corrected by refunding and re-recording.
 */
export default function PaymentsIndex({
    payments,
    filters,
    options,
    totals,
    can,
}: Props) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/payments',
    );

    const hasFilters = Object.values(filters).some(Boolean);

    const setFilter = (key: string, value: string) => {
        router.get(
            '/admin/payments',
            { ...filters, [key]: value === '__all' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title={t('admin.payments.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.payments.title')}
                    </h1>

                    {can.record && <RecordPaymentForm />}
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
                            placeholder={t('admin.payments.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <Select
                        value={filters.status ?? '__all'}
                        onValueChange={(value) => setFilter('status', value)}
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

                    {hasFilters && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/payments')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {payments.data.length === 0 ? (
                    <EmptyState
                        icon={Wallet}
                        title={t('common.states.no_results')}
                        description={t('admin.payments.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {payments.data.map((payment) => (
                            <Link
                                key={payment.ulid}
                                href={`/admin/payments/${payment.ulid}`}
                                className="hover:bg-accent/50 flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {payment.payer_name}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {payment.for ?? ''}
                                        {payment.receipt_no
                                            ? ` · ${payment.receipt_no}`
                                            : ''}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <span className="text-muted-foreground hidden text-sm sm:inline">
                                        {formatDate(
                                            payment.paid_at ??
                                                payment.created_at,
                                        )}
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {formatCurrency(payment.amount)}
                                    </span>
                                    <Badge
                                        variant={
                                            STATUS_VARIANT[payment.status] ??
                                            'secondary'
                                        }
                                    >
                                        {payment.status_label}
                                    </Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination meta={payments.meta} />
            </div>
        </AdminLayout>
    );
}

function Totals({ totals }: { totals?: LedgerTotals }) {
    const { t } = useTranslation();

    if (!totals) {
        return null;
    }

    const cells: Array<[string, number]> = [
        [t('admin.payments.received'), totals.received],
        [t('admin.payments.pending'), totals.pending],
        [t('admin.payments.refunded_total'), totals.refunded],
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

/**
 * Recording money already received.
 *
 * The method note is free text on purpose: "bKash 01712xxxxxx, trx ABC123" is
 * the trail somebody follows a year later, and no dropdown would have covered
 * it.
 */
function RecordPaymentForm() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        payable_kind: 'fee',
        payable_id: '',
        amount: '',
        method: '',
        reference: '',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/payments', {
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
                {t('admin.payments.record')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="payable_kind">
                                {t('admin.payments.payable_kind')}
                            </Label>
                            <Select
                                value={form.data.payable_kind}
                                onValueChange={(value) =>
                                    form.setData('payable_kind', value)
                                }
                            >
                                <SelectTrigger id="payable_kind">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {[
                                        'fee',
                                        'registration',
                                        'donation',
                                        'sponsor',
                                    ].map((kind) => (
                                        <SelectItem key={kind} value={kind}>
                                            {t(`admin.payments.kind_${kind}`)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.payable_kind} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="payable_id">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="payable_id"
                                type="number"
                                value={form.data.payable_id}
                                onChange={(e) =>
                                    form.setData('payable_id', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.payable_id} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="amount">
                                {t('member.events.amount_due')}
                            </Label>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                value={form.data.amount}
                                onChange={(e) =>
                                    form.setData('amount', e.target.value)
                                }
                            />
                            <InputError message={form.errors.amount} />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="method">
                            {t('admin.payments.method')}
                        </Label>
                        <Input
                            id="method"
                            value={form.data.method}
                            placeholder={t('admin.payments.method_hint')}
                            onChange={(e) =>
                                form.setData('method', e.target.value)
                            }
                        />
                        <InputError message={form.errors.method} />
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
