import { Deferred, Link } from '@inertiajs/react';
import { ArrowRight, CircleHelp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { formatYear } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import type { Faq } from '@/types/content';

type Props = {
    organisation: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
    };
    school: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
        eiin: string | null;
        address: string | null;
    };
    stats?: { milestones: number; albums: number };
    faqs?: Faq[];
};

export default function About({ organisation, school, faqs }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.about.title')}
            description={t('public.about.subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.about.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.about.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-background">
                <div className="mx-auto max-w-3xl space-y-10 px-4 py-10">
                    <section>
                        <h2 className="text-brand-green-900 text-xl font-semibold">
                            {t('public.about.association')}
                        </h2>

                        {/* Their own name, in their own script. */}
                        {organisation.name_bn && (
                            <p
                                className="font-bangla text-brand-green-800 mt-2 text-lg"
                                lang="bn"
                            >
                                {organisation.name_bn}
                            </p>
                        )}

                        <p className="text-muted-foreground mt-3 leading-relaxed">
                            {t('public.about.association_body')}
                        </p>

                        {organisation.established && (
                            <p className="text-muted-foreground mt-2 text-sm">
                                {t('public.about.founded', {
                                    year: organisation.established,
                                })}
                            </p>
                        )}
                    </section>

                    <section>
                        <h2 className="text-brand-green-900 text-xl font-semibold">
                            {t('public.about.school')}
                        </h2>

                        {school.name_bn && (
                            <p
                                className="font-bangla text-brand-green-800 mt-2 text-lg"
                                lang="bn"
                            >
                                {school.name_bn}
                            </p>
                        )}

                        <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                            {school.name_en && (
                                <Detail label="" value={school.name_en} />
                            )}
                            {school.established && (
                                <Detail
                                    label={t('public.school.founded')}
                                    value={formatYear(school.established)}
                                />
                            )}
                            {school.eiin && (
                                <Detail
                                    label="EIIN"
                                    value={String(school.eiin)}
                                />
                            )}
                            {school.address && (
                                <Detail label="" value={school.address} />
                            )}
                        </dl>

                        <Button
                            variant="outline"
                            size="sm"
                            className="mt-4"
                            asChild
                        >
                            <Link href="/about/school">
                                {t('public.about.school_link')}
                                <ArrowRight
                                    className="ms-1 size-3.5 rtl:rotate-180"
                                    aria-hidden="true"
                                />
                            </Link>
                        </Button>
                    </section>

                    <Deferred
                        data="faqs"
                        fallback={<Skeleton className="h-40 w-full" />}
                    >
                        <Faqs items={faqs ?? []} />
                    </Deferred>

                    <section className="bg-brand-cream rounded-lg border p-6 text-center">
                        <CircleHelp
                            className="text-brand-green-800 mx-auto size-6"
                            aria-hidden="true"
                        />
                        <p className="mt-3 font-medium">
                            {t('public.about.contact_cta')}
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href="/contact">
                                {t('public.nav.contact')}
                            </Link>
                        </Button>
                    </section>
                </div>
            </div>
        </PublicLayout>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            {label && (
                <dt className="text-muted-foreground text-xs">{label}</dt>
            )}
            <dd>{value}</dd>
        </div>
    );
}

function Faqs({ items }: { items: Faq[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section>
            <h2 className="text-brand-green-900 text-xl font-semibold">
                {t('public.about.faqs')}
            </h2>

            <div className="mt-4 space-y-3">
                {items.map((faq) => (
                    <Card key={faq.id}>
                        <CardContent className="pt-5">
                            <p className="font-medium">{faq.question}</p>
                            <p className="text-muted-foreground mt-1 text-sm leading-relaxed whitespace-pre-wrap">
                                {faq.answer}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </section>
    );
}
