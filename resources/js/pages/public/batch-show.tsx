import { Link } from '@inertiajs/react';
import { ArrowLeft, Lock, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';

type Batch = {
    slug: string;
    name: string;
    description: string | null;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};

/**
 * A single batch page for the public.
 *
 * Shows the count and invites people in. The member list lives behind the
 * directory, which requires an approved membership.
 */
export default function BatchShow({ batch }: { batch: Batch }) {
    const { t, locale } = useTranslation();

    return (
        <PublicLayout
            title={batch.name}
            description={batch.description ?? undefined}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-4xl px-4 py-14">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-6 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <Link href="/batches">
                            <ArrowLeft
                                className="me-1 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.nav.batches')}
                        </Link>
                    </Button>

                    <h1 className="text-3xl font-semibold">{batch.name}</h1>

                    <p className="mt-3 flex items-center gap-2 text-white/70">
                        <Users className="size-4" aria-hidden="true" />
                        {t('public.batches.member_count', {
                            count: formatNumber(batch.members_count, locale),
                        })}
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-4xl px-4 py-10">
                {batch.description && (
                    <p className="leading-relaxed">{batch.description}</p>
                )}

                <div className="bg-brand-green-100 text-brand-green-900 mt-8 flex flex-col items-start gap-3 rounded-lg p-5 text-sm sm:flex-row sm:items-center">
                    <Lock className="size-4 shrink-0" aria-hidden="true" />
                    <p className="flex-1">
                        {t('public.directory.members_only_note')}
                    </p>
                    <Button asChild size="sm">
                        <Link href="/join">{t('public.nav.join')}</Link>
                    </Button>
                </div>
            </div>
        </PublicLayout>
    );
}
