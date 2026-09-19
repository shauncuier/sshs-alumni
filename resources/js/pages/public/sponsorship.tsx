import { Handshake, Mail } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Package = {
    slug: string;
    name: string;
    tier: string;
    tier_label: string;
    amount: number | null;
    currency: string;
    benefits: string | null;
    /** Whether any remain. Never how many. */
    available: boolean;
};

type Sponsor = {
    name: string;
    website: string | null;
    logo_url: string | null;
    tier_label: string | null;
};

type Props = {
    packages: Package[];
    sponsors: Sponsor[];
};

export default function Sponsorship({ packages, sponsors }: Props) {
    const { t } = useTranslation();

    const email = useSetting<string>('contact.email');

    return (
        <PublicLayout
            title={t('public.giving.sponsorship_title')}
            description={t('public.giving.sponsorship_subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Handshake className="size-3.5" />
                        Partnership & Sponsorship
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.giving.sponsorship_title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.giving.sponsorship_subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-12 px-4 sm:px-6 lg:px-8">
                    {/* Packages Section */}
                    <section>
                        <div className="flex items-center justify-between border-b border-slate-200/80 pb-4">
                            <h2 className="text-2xl font-bold text-slate-900">
                                {t('public.giving.packages')}
                            </h2>
                            {email && (
                                <a
                                    href={`mailto:${email}`}
                                    className="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-teal-700 hover:text-teal-800 transition"
                                >
                                    <Mail className="size-4" />
                                    <span>{t('public.giving.enquire')}</span>
                                </a>
                            )}
                        </div>

                        {packages.length === 0 ? (
                            <p className="mt-6 text-sm text-slate-500">
                                {t('common.states.empty')}
                            </p>
                        ) : (
                            <div className="mt-6 grid gap-6 sm:grid-cols-2">
                                {packages.map((pkg) => (
                                    <div
                                        key={pkg.slug}
                                        className="flex flex-col justify-between rounded-3xl border border-teal-500/15 bg-white/90 p-6 sm:p-7 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-xl"
                                    >
                                        <div className="space-y-3">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <h3 className="text-xl font-bold text-slate-900">
                                                    {pkg.name}
                                                </h3>
                                                <div className="flex items-center gap-1.5">
                                                    <span className="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200/60">
                                                        {pkg.tier_label}
                                                    </span>
                                                    {!pkg.available && (
                                                        <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 border border-amber-200">
                                                            {t(
                                                                'public.giving.package_full',
                                                            )}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {pkg.amount !== null && (
                                                <p className="text-2xl font-extrabold text-teal-700 tabular-nums">
                                                    {formatCurrency(pkg.amount)}
                                                </p>
                                            )}

                                            {pkg.benefits && (
                                                <p className="text-sm text-slate-600 leading-relaxed whitespace-pre-line pt-2 border-t border-slate-100">
                                                    {pkg.benefits}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Sponsors Section */}
                    <section className="rounded-3xl border border-teal-500/15 bg-white/80 p-8 shadow-sm backdrop-blur-md">
                        <h2 className="text-xl font-bold text-slate-900 border-b border-slate-100 pb-4">
                            {t('public.giving.our_sponsors')}
                        </h2>

                        {sponsors.length === 0 ? (
                            <div className="py-6">
                                <EmptyState
                                    icon={Handshake}
                                    title={t('public.giving.our_sponsors')}
                                    description={t('public.giving.sponsors_empty')}
                                />
                            </div>
                        ) : (
                            <ul className="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                                {sponsors.map((sponsor) => (
                                    <li
                                        key={sponsor.name}
                                        className="flex flex-col items-center justify-center rounded-2xl border border-slate-100 bg-white p-5 shadow-xs transition hover:border-teal-500/30 hover:shadow-md"
                                    >
                                        {sponsor.logo_url ? (
                                            <img
                                                src={sponsor.logo_url}
                                                alt={sponsor.name}
                                                className="h-16 w-auto object-contain"
                                            />
                                        ) : (
                                            <span className="font-bold text-slate-800 text-center text-sm">
                                                {sponsor.name}
                                            </span>
                                        )}
                                        {sponsor.tier_label && (
                                            <span className="mt-2 inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700">
                                                {sponsor.tier_label}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </PublicLayout>
    );
}
