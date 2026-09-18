import { Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { BrandMark } from '@/components/shared/brand-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { Payment } from '@/types/money';

type Props = { payment: Payment };

/**
 * A receipt.
 *
 * Printed through the browser rather than dompdf: Bengali conjunct shaping in
 * dompdf is unreliable, and this document carries the association's and the
 * school's names, which are exactly the Bangla that survives on this platform.
 *
 * @see docs/09-payments.md section 5
 */
export default function ReceiptPage({ payment }: Props) {
    const { t } = useTranslation();

    const orgNameBn = useSetting<string>('organization.name_bn');
    const orgNameEn = useSetting<string>('organization.name_en');
    const address = useSetting<string>('school.address');

    const rows: Array<[string, string | null]> = [
        [t('admin.payments.receipt_no'), payment.receipt_no],
        [t('admin.payments.paid_at'), formatDate(payment.paid_at)],
        [t('admin.payments.for'), payment.for ?? null],
        [
            t('admin.payments.method'),
            (payment.meta?.method as string | undefined) ?? null,
        ],
        [t('admin.payments.reference'), payment.gateway_txn_id],
    ];

    return (
        <MemberLayout title={payment.receipt_no ?? t('member.money.receipt')}>
            <div className="mx-auto max-w-lg space-y-5">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="print:hidden"
                >
                    <Link href="/my/payments">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('member.money.payments_title')}
                    </Link>
                </Button>

                <Card>
                    <CardContent className="space-y-6 p-6">
                        <div className="flex items-start justify-between gap-4 border-b pb-4">
                            <BrandMark size="md" withName />
                            <div className="text-end">
                                <p className="text-muted-foreground text-xs">
                                    {t('member.money.receipt')}
                                </p>
                                <p className="tabular-id font-semibold">
                                    {payment.receipt_no}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p className="text-muted-foreground text-xs">
                                {t('admin.payments.payer')}
                            </p>
                            <p className="font-medium">{payment.payer_name}</p>
                        </div>

                        <div className="bg-brand-green-100 rounded-lg p-4 text-center">
                            <p className="text-brand-green-900 text-3xl font-semibold tabular-nums">
                                {formatCurrency(payment.amount)}
                            </p>
                        </div>

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

                        <div className="text-muted-foreground border-t pt-4 text-center text-xs">
                            {orgNameBn && (
                                <p lang="bn" className="text-foreground">
                                    {orgNameBn}
                                </p>
                            )}
                            {orgNameEn && <p>{orgNameEn}</p>}
                            {address && <p className="mt-1">{address}</p>}
                        </div>
                    </CardContent>
                </Card>

                <Button
                    variant="outline"
                    size="sm"
                    className="print:hidden"
                    onClick={() => window.print()}
                >
                    <Printer className="me-1 size-4" aria-hidden="true" />
                    {t('member.money.print')}
                </Button>
            </div>
        </MemberLayout>
    );
}
