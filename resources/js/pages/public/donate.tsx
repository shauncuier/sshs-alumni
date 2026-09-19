import { Deferred } from '@inertiajs/react';
import { Heart, Mail, Phone, Users } from 'lucide-react';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Heart className="size-3.5 text-teal-300" />
                        Giving & Support
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.giving.donate_title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.giving.donate_subtitle')}
                    </p>

                    <Deferred
                        data="total"
                        fallback={
                            <Skeleton className="mt-8 h-20 w-64 rounded-2xl bg-white/10" />
                        }
                    >
                        <Total total={total} />
                    </Deferred>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 sm:px-6 lg:px-8 lg:grid-cols-3">
                    <div className="glass-panel-light rounded-3xl p-6 sm:p-8 border border-teal-500/15 shadow-sm lg:col-span-2">
                        <h2 className="flex items-center gap-2.5 text-xl font-bold text-slate-900">
                            <div className="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                <Heart className="size-4" aria-hidden="true" />
                            </div>
                            {t('public.giving.how')}
                        </h2>

                        <p className="mt-4 text-base leading-relaxed text-slate-700">
                            {t('public.giving.how_hint')}
                        </p>

                        <div className="mt-6 space-y-3 border-t border-slate-100 pt-6">
                            {phone && (
                                <div className="flex items-center gap-3 text-sm text-slate-700">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                        <Phone className="size-4" aria-hidden="true" />
                                    </div>
                                    <a
                                        href={`tel:${phone}`}
                                        className="tabular-id font-semibold hover:text-teal-700 transition"
                                    >
                                        {phone}
                                    </a>
                                </div>
                            )}
                            {email && (
                                <div className="flex items-center gap-3 text-sm text-slate-700">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                        <Mail className="size-4" aria-hidden="true" />
                                    </div>
                                    <a
                                        href={`mailto:${email}`}
                                        className="break-all font-semibold hover:text-teal-700 transition"
                                    >
                                        {email}
                                    </a>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-3xl border border-teal-500/15 bg-white/90 p-6 shadow-sm backdrop-blur-md">
                        <div className="flex items-center gap-2 border-b border-slate-100 pb-4">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-800">
                                <Users className="size-4" />
                            </div>
                            <h2 className="font-bold text-slate-900">
                                {t('public.giving.wall')}
                            </h2>
                        </div>

                        {recent.length === 0 ? (
                            <p className="mt-4 text-sm text-slate-500">
                                {t('public.giving.wall_empty')}
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-3 text-sm">
                                {recent.map((donor, index) => (
                                    <li
                                        key={`${donor.donor_name}-${index}`}
                                        className="flex items-center justify-between gap-3 border-b border-slate-50 pb-2.5 last:border-0"
                                    >
                                        <span className="min-w-0 truncate font-medium text-slate-800">
                                            {donor.donor_name}
                                        </span>
                                        <span className="shrink-0 text-xs text-slate-400 tabular-nums">
                                            {formatDate(donor.received_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <p className="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-400 leading-relaxed">
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
        <div className="mt-8 inline-block rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-md shadow-inner">
            <p className="text-xs uppercase tracking-wider font-semibold text-slate-300">
                {t('public.giving.total_raised')}
            </p>
            <p className="mt-1 text-3xl sm:text-4xl font-extrabold text-amber-400 tabular-nums">
                {formatCurrency(total.amount)}
            </p>
            <p className="mt-1 text-xs sm:text-sm text-slate-300">
                {choice('public.giving.donors', total.donors, {
                    count: formatNumber(total.donors),
                })}
            </p>
        </div>
    );
}
