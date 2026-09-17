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
                <EmptyState
                    title={t('jubilee.faq.title')}
                    description={t('jubilee.faq.empty')}
                />
            ) : (
                <dl className="space-y-4">
                    {faqs.map((faq) => (
                        <div
                            key={faq.question}
                            className="bg-card rounded-lg border p-5"
                        >
                            <dt className="font-medium">{faq.question}</dt>
                            <dd className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                {faq.answer}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </JubileeLayout>
    );
}
