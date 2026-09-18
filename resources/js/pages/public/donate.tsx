import { Deferred } from '@inertiajs/react';
import { Heart, Mail, Phone } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate, formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Donor = {
    donor_name: string;
    amount: number;
    currency: string;
    campaign: string | null;
    received_at: string | null;
};

type Props = {
    recent: Donor[];
    total?: { amount: number; donors: number; currency: string };
};

/**
 * How to give, and who already has.
 *
 * No card form. The association collects in cash, by transfer and through
 * mobile financial services settled outside the platform, so this page says so
 * plainly — an online payment button that does not work would be worse than
 * honest instructions.
 *
 * Donors who asked to remain anonymous are absent from the payload entirely,
 * and the page says that too.
 */
export default function Donate({ recent, total }: Props) {
    const { t } = useTranslation();

    const phone = useSetting<string>('contact.phone');
    const email = useSetting<string>('contact.email');

    return (
        <PublicLayout
            title={t('public.giving.donate_title')}
            description={t('public.giving.donate_subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-4xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.giving.donate_title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.giving.donate_subtitle')}
                    </p>

                    <Deferred
                        data="total"
                        fallback={
                            <Skeleton className="mt-8 h-16 w-64 bg-white/10" />
                        }
                    >
                        <Total total={total} />
                    </Deferred>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto grid max-w-4xl gap-8 px-4 py-10 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardContent className="space-y-4 p-6">
                            <h2 className="flex items-center gap-2 text-lg font-semibold">
                                <Heart
                                    className="text-brand-green-700 size-5"
                                    aria-hidden="true"
                                />
                                {t('public.giving.how')}
                            </h2>

                            <p className="text-muted-foreground leading-relaxed">
                                {t('public.giving.how_hint')}
                            </p>

                            <ul className="space-y-2 text-sm">
                                {phone && (
                                    <li className="flex items-center gap-2">
                                        <Phone
                                            className="size-4 shrink-0"
                                            aria-hidden="true"
                                        />
                                        <a
                                            href={`tel:${phone}`}
                                            className="tabular-id hover:underline"
                                        >
                                            {phone}
                                        </a>
                                    </li>
                                )}
                                {email && (
                                    <li className="flex items-center gap-2">
                                        <Mail
                                            className="size-4 shrink-0"
                                            aria-hidden="true"
                                        />
                                        <a
                                            href={`mailto:${email}`}
                                            className="break-all hover:underline"
                                        >
                                            {email}
                                        </a>
                                    </li>
                                )}
                            </ul>
                        </CardContent>
                    </Card>

                    <div>
                        <h2 className="font-semibold">
                            {t('public.giving.wall')}
                        </h2>

                        {recent.length === 0 ? (
                            <p className="text-muted-foreground mt-3 text-sm">
                                {t('public.giving.wall_empty')}
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2 text-sm">
                                {recent.map((donor, index) => (
                                    <li
                                        key={`${donor.donor_name}-${index}`}
                                        className="flex justify-between gap-3"
                                    >
                                        <span className="min-w-0 truncate">
                                            {donor.donor_name}
                                        </span>
                                        <span className="text-muted-foreground shrink-0 tabular-nums">
                                            {formatDate(donor.received_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <p className="text-muted-foreground mt-4 text-xs">
                            {t('public.giving.wall_note')}
                        </p>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

function Total({
    total,
}: {
    total?: { amount: number; donors: number; currency: string };
}) {
    const { t, choice } = useTranslation();

    if (!total) {
        return null;
    }

    return (
        <div className="mt-8">
            <p className="text-sm text-white/60">
                {t('public.giving.total_raised')}
            </p>
            <p className="text-brand-gold-500 text-4xl font-semibold tabular-nums">
                {formatCurrency(total.amount)}
            </p>
            <p className="mt-1 text-sm text-white/70">
                {choice('public.giving.donors', total.donors, {
                    count: formatNumber(total.donors),
                })}
            </p>
        </div>
    );
}
