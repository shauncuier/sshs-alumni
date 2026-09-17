import { Link } from '@inertiajs/react';
import { GraduationCap, Lock } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Batch = {
    slug: string;
    name: string;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};

type Props = {
    batches: Batch[];
    totals: { batches: number; members: number };
};

/**
 * Public batch index — COUNTS ONLY.
 *
 * The directory is members-only. Listing real names here would hand a scraper
 * exactly what the directory withholds, so this page says how many and invites
 * people to join rather than showing who.
 */
export default function Batches({ batches, totals }: Props) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout
            title={t('public.nav.batches')}
            description={t('public.batches.subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-7xl px-4 py-12 text-center">
                    <h1 className="text-3xl font-semibold">
                        {t('public.nav.batches')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.batches.subtitle')}
                    </p>

                    <dl className="mt-8 flex justify-center gap-10">
                        <div>
                            <dd className="text-3xl font-semibold">
                                {formatNumber(totals.batches)}
                            </dd>
                            <dt className="text-sm text-white/60">
                                {t('public.stats.batches')}
                            </dt>
                        </div>
                        <div>
                            <dd className="text-3xl font-semibold">
                                {formatNumber(totals.members)}
                            </dd>
                            <dt className="text-sm text-white/60">
                                {t('public.stats.members')}
                            </dt>
                        </div>
                    </dl>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-10">
                <div className="bg-brand-green-100 text-brand-green-900 mb-8 flex items-start gap-3 rounded-lg p-4 text-sm">
                    <Lock
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <p>{t('public.directory.members_only_note')}</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {batches.map((batch) => (
                        <Link key={batch.slug} href={`/batches/${batch.slug}`}>
                            <Card className="h-full transition-shadow hover:shadow-md">
                                <CardContent className="flex items-center gap-3 p-5">
                                    <GraduationCap
                                        className="text-brand-green-800 size-8 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {batch.name}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {choice(
                                                'public.batches.member_count',
                                                batch.members_count,
                                                {
                                                    count: formatNumber(
                                                        batch.members_count,
                                                    ),
                                                },
                                            )}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </PublicLayout>
    );
}
