import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Undo2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDateTime } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Payment } from '@/types/money';

type Props = {
    payment: Payment;
    can: { refund: boolean };
};

export default function PaymentShow({ payment, can }: Props) {
    const { t } = useTranslation();

    const rows: Array<[string, string | null]> = [
        [t('admin.payments.receipt_no'), payment.receipt_no],
        [t('admin.payments.invoice_no'), payment.invoice_no],
        [t('admin.payments.for'), payment.for ?? null],
        [t('admin.payments.payer'), payment.payer_name],
        [t('admin.payments.paid_at'), formatDateTime(payment.paid_at)],
        [t('admin.payments.reference'), payment.gateway_txn_id],
        [
            t('admin.payments.method'),
            (payment.meta?.method as string | undefined) ?? null,
        ],
        [t('admin.payments.recorded_by'), payment.recorded_by ?? null],
    ];

    return (
        <AdminLayout title={payment.receipt_no ?? payment.payer_name}>
            <div className="mx-auto max-w-2xl space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/admin/payments">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('admin.payments.title')}
                    </Link>
                </Button>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex flex-wrap items-center justify-between gap-3 text-base">
                            <span className="text-2xl font-semibold tabular-nums">
                                {formatCurrency(payment.amount)}
                            </span>
                            <Badge
                                variant={
                                    payment.status === 'paid'
                                        ? 'default'
                                        : payment.status === 'refunded'
                                          ? 'outline'
                                          : 'secondary'
                                }
                            >
                                {payment.status_label}
                            </Badge>
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <dl className="space-y-2 text-sm">
                            {rows
                                .filter(([, value]) => Boolean(value))
                                .map(([label, value]) => (
                                    <div
                                        key={label}
                                        className="flex justify-between gap-4"
                                    >
                                        <dt className="text-muted-foreground">
                                            {label}
                                        </dt>
                                        <dd className="min-w-0 text-end">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                        </dl>

                        {payment.notes && (
                            <p className="text-muted-foreground border-t pt-4 text-sm whitespace-pre-line">
                                {payment.notes}
                            </p>
                        )}

                        {payment.refunded_at && (
                            <div className="border-destructive/40 bg-destructive/5 rounded-md border p-3 text-sm">
                                <p className="font-medium">
                                    {t('admin.payments.refunded')}
                                </p>
                                <p className="text-muted-foreground mt-1">
                                    {formatDateTime(payment.refunded_at)}
                                    {payment.refund_reason
                                        ? ` · ${payment.refund_reason}`
                                        : ''}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {can.refund && <RefundForm payment={payment} />}
            </div>
        </AdminLayout>
    );
}

/**
 * Refunding.
 *
 * The original row is preserved with its receipt number. A ledger that erases
 * its mistakes cannot be audited, and the form says so.
 */
function RefundForm({ payment }: { payment: Payment }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({ reason: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (!window.confirm(t('admin.payments.refund_confirm'))) {
            return;
        }

        form.post(`/admin/payments/${payment.ulid}/refund`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <Undo2 className="me-1 size-4" aria-hidden="true" />
                {t('admin.payments.refund')}
            </Button>
        );
    }

    return (
        <Card>
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <p className="text-muted-foreground text-sm">
                        {t('admin.payments.refund_hint')}
                    </p>

                    <div className="space-y-1.5">
                        <Label htmlFor="reason">
                            {t('admin.payments.refund_reason')}
                        </Label>
                        <Input
                            id="reason"
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.reason} />
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            {t('admin.payments.refund')}
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
