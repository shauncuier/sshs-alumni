import { JubileeLayout } from '@/components/public/jubilee-layout';
import { EmptyState } from '@/components/shared/empty-state';
import { useTranslation } from '@/hooks/use-translation';
import type { PublicEvent } from '@/types/event';

type Props = {
    event: PublicEvent | null;
    faqs: Array<{ question: string; answer: string }>;
};

export default function Faq({ event, faqs }: Props) {
    const { t } = useTranslation();

    return (
        <JubileeLayout
            event={event}
            active="faq"
            title={t('jubilee.faq.title')}
        >
            {faqs.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-teal-200/80 p-10 text-center">
                    <EmptyState
                        title={t('jubilee.faq.title')}
                        description={t('jubilee.faq.empty')}
                    />
                </div>
            ) : (
                <dl className="space-y-4">
                    {faqs.map((faq) => (
                        <div
                            key={faq.question}
                            className="glass-card-hover rounded-2xl border border-teal-500/15 bg-white/80 p-6 shadow-xs transition-all duration-200 hover:border-teal-500/30"
                        >
                            <dt className="flex items-start gap-3 text-base font-bold text-slate-900">
                                <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-800">
                                    <span className="text-xs font-bold">Q</span>
                                </div>
                                <span>{faq.question}</span>
                            </dt>
                            <dd className="mt-2.5 ps-8 text-sm leading-relaxed text-slate-600">
                                {faq.answer}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </JubileeLayout>
    );
}
