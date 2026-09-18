import { Link } from '@inertiajs/react';
import { Receipt, Wallet } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { Payment } from '@/types/money';
import type { Paginated } from '@/types/member';

type Outstanding = {
    id: number;
    period_label: string;
    amount: number;
    currency: string;
    due_at: string | null;
    is_overdue: boolean;
};

type Props = {
    payments: Paginated<Payment>;
    outstanding: Outstanding[];
    totals: { paid: number; currency: string };
};

export default function MemberPayments({
    payments,
    outstanding,
    totals,
}: Props) {
    const { t } = useTranslation();

    return (
        <MemberLayout title={t('member.money.payments_title')}>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('member.money.payments_title')}
                    </h1>

                    <div className="text-end">
                        <p className="text-muted-foreground text-xs">
                            {t('member.money.total_paid')}
                        </p>
                        <p className="text-xl font-semibold tabular-nums">
                            {formatCurrency(totals.paid)}
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {t('member.money.outstanding')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {outstanding.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('member.money.outstanding_empty')}
                            </p>
                        ) : (
                            <>
                                <ul className="divide-y">
                                    {outstanding.map((fee) => (
                                        <li
                                            key={fee.id}
                                            className="flex flex-wrap items-center justify-between gap-2 py-2"
                                        >
                                            <span className="min-w-0">
                                                <span className="block text-sm font-medium">
                                                    {fee.period_label}
                                                </span>
                                                {fee.due_at && (
                                                    <span className="text-muted-foreground block text-xs">
                                                        {formatDate(fee.due_at)}
                                                    </span>
                                                )}
                                            </span>

                                            <span className="flex items-center gap-2">
                                                {fee.is_overdue && (
                                                    <Badge variant="destructive">
                                                        {t(
                                                            'member.money.overdue',
                                                        )}
                                                    </Badge>
                                                )}
                                                <span className="font-medium tabular-nums">
                                                    {formatCurrency(fee.amount)}
                                                </span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                <p className="text-muted-foreground mt-3 text-xs">
                                    {t('member.money.pay_hint')}
                                </p>
                            </>
                        )}
                    </CardContent>
                </Card>

                {payments.data.length === 0 ? (
                    <EmptyState
                        icon={Wallet}
                        title={t('common.states.empty')}
                        description={t('member.money.payments_empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {payments.data.map((payment) => (
                            <div
                                key={payment.ulid}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {payment.for ?? payment.payer_name}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {formatDate(payment.paid_at)}
                                        {payment.receipt_no
                                            ? ` · ${payment.receipt_no}`
                                            : ''}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium tabular-nums">
                                        {formatCurrency(payment.amount)}
                                    </span>

                                    <Badge
                                        variant={
                                            payment.status === 'paid'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {payment.status_label}
                                    </Badge>

                                    {payment.status === 'paid' && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={`/my/payments/${payment.ulid}/receipt`}
                                            >
                                                <Receipt
                                                    className="me-1 size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t('member.money.receipt')}
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={payments.meta} />
            </div>
        </MemberLayout>
    );
}
