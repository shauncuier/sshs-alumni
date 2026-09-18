import { ScrollText } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatYear } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Milestone } from '@/types/content';

type Props = {
    school: {
        name_bn: string | null;
        name_en: string | null;
        established: number | null;
        eiin: string | null;
        board: string | null;
        address: string | null;
        head_teacher: string | null;
        motto_en: string | null;
    };
    milestones: Milestone[];
};

/**
 * The school's fifty years.
 *
 * The timeline is seeded with two entries and says so. A timeline with two
 * milestones and no explanation looks broken; one that states outright that
 * the rest is still being collected looks like what it is, and invites the
 * people who remember to fill it in.
 */
export default function School({ school, milestones }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.school.title')}
            description={t('public.school.subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {school.name_en ?? t('public.school.title')}
                    </h1>

                    {school.name_bn && (
                        <p
                            className="font-bangla text-brand-gold-500 mt-2 text-xl"
                            lang="bn"
                        >
                            {school.name_bn}
                        </p>
                    )}

                    {school.motto_en && (
                        <p className="mt-3 text-white/70 italic">
                            {school.motto_en}
                        </p>
                    )}

                    <dl className="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                        {school.established && (
                            <Fact
                                label={t('public.school.founded')}
                                value={formatYear(school.established)}
                            />
                        )}
                        {school.eiin && (
                            <Fact label="EIIN" value={String(school.eiin)} />
                        )}
                        {school.board && (
                            <Fact
                                label={t('public.school.board')}
                                value={school.board}
                            />
                        )}
                        {school.head_teacher && (
                            <Fact
                                label={t('public.school.head_teacher')}
                                value={school.head_teacher}
                            />
                        )}
                        {school.address && (
                            <Fact label="" value={school.address} />
                        )}
                    </dl>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <h2 className="text-brand-green-900 text-xl font-semibold">
                        {t('public.school.timeline')}
                    </h2>

                    {milestones.length === 0 ? (
                        <p className="text-muted-foreground mt-3 text-sm">
                            {t('public.school.timeline_empty')}
                        </p>
                    ) : (
                        <>
                            <ol className="border-brand-gold-500/40 mt-6 space-y-8 border-s ps-6">
                                {milestones.map((milestone) => (
                                    <li
                                        key={milestone.id ?? milestone.year}
                                        className="relative"
                                    >
                                        <span
                                            className={cn(
                                                'absolute -start-[1.9rem] mt-1.5 size-3 rounded-full',
                                                milestone.is_highlighted
                                                    ? 'bg-brand-gold-600'
                                                    : 'bg-brand-green-800',
                                            )}
                                            aria-hidden="true"
                                        />

                                        <p className="text-brand-green-900 text-lg font-semibold">
                                            {milestone.year}
                                            {milestone.date_label
                                                ? ` · ${milestone.date_label}`
                                                : ''}
                                        </p>

                                        <p className="font-medium">
                                            {milestone.title}
                                        </p>

                                        {milestone.description && (
                                            <p className="text-muted-foreground mt-1 text-sm leading-relaxed">
                                                {milestone.description}
                                            </p>
                                        )}

                                        {milestone.image_url && (
                                            <img
                                                src={milestone.image_url}
                                                alt=""
                                                loading="lazy"
                                                className="mt-3 h-40 w-full rounded-md object-cover sm:w-80"
                                            />
                                        )}
                                    </li>
                                ))}
                            </ol>

                            {/* Said outright rather than left to look broken. */}
                            <p className="text-muted-foreground mt-8 flex items-start gap-2 rounded-md border border-dashed px-4 py-3 text-sm">
                                <ScrollText
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                {t('public.school.timeline_partial')}
                            </p>
                        </>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            {label && <dt className="text-xs text-white/60">{label}</dt>}
            <dd className="text-white/90">{value}</dd>
        </div>
    );
}
