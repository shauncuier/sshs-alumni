import { Handshake } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-4xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.giving.sponsorship_title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.giving.sponsorship_subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-4xl space-y-10 px-4 py-10">
                    <section>
                        <h2 className="text-brand-green-900 text-xl font-semibold">
                            {t('public.giving.packages')}
                        </h2>

                        {packages.length === 0 ? (
                            <p className="text-muted-foreground mt-3 text-sm">
                                {t('common.states.empty')}
                            </p>
                        ) : (
                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                {packages.map((pkg) => (
                                    <Card key={pkg.slug}>
                                        <CardContent className="space-y-2 p-5">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {pkg.name}
                                                </h3>
                                                <Badge variant="secondary">
                                                    {pkg.tier_label}
                                                </Badge>
                                                {!pkg.available && (
                                                    <Badge variant="outline">
                                                        {t(
                                                            'public.giving.package_full',
                                                        )}
                                                    </Badge>
                                                )}
                                            </div>

                                            {pkg.amount !== null && (
                                                <p className="text-brand-green-800 text-lg font-semibold tabular-nums">
                                                    {formatCurrency(pkg.amount)}
                                                </p>
                                            )}

                                            {pkg.benefits && (
                                                <p className="text-muted-foreground text-sm leading-relaxed whitespace-pre-line">
                                                    {pkg.benefits}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}

                        {email && (
                            <p className="text-muted-foreground mt-4 text-sm">
                                {t('public.giving.enquire')}:{' '}
                                <a
                                    href={`mailto:${email}`}
                                    className="break-all underline"
                                >
                                    {email}
                                </a>
                            </p>
                        )}
                    </section>

                    <section>
                        <h2 className="text-brand-green-900 text-xl font-semibold">
                            {t('public.giving.our_sponsors')}
                        </h2>

                        {sponsors.length === 0 ? (
                            <EmptyState
                                icon={Handshake}
                                title={t('public.giving.our_sponsors')}
                                description={t('public.giving.sponsors_empty')}
                            />
                        ) : (
                            <ul className="mt-4 flex flex-wrap items-center gap-6">
                                {sponsors.map((sponsor) => (
                                    <li
                                        key={sponsor.name}
                                        className="text-center"
                                    >
                                        {sponsor.logo_url ? (
                                            <img
                                                src={sponsor.logo_url}
                                                alt={sponsor.name}
                                                className="h-14 w-auto object-contain"
                                            />
                                        ) : (
                                            <span className="font-medium">
                                                {sponsor.name}
                                            </span>
                                        )}
                                        {sponsor.tier_label && (
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {sponsor.tier_label}
                                            </p>
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
