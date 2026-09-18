import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';

type Props = {
    page: {
        slug: string;
        title: string;
        body: string | null;
        updated_at: string | null;
        meta_title: string | null;
        meta_description: string | null;
    };
};

/**
 * A standing page — the privacy policy, the terms, anything else the committee
 * needs a URL for.
 *
 * Rendered as text with preserved line breaks. These are legal-ish documents
 * typed into a textarea by a committee member; treating them as HTML would
 * mean a stray angle bracket silently swallows a clause.
 */
export default function Page({ page }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={page.meta_title ?? page.title}
            description={page.meta_description ?? undefined}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <h1 className="text-3xl font-semibold">{page.title}</h1>

                    {page.updated_at && (
                        <p className="mt-2 text-sm text-white/60">
                            {t('public.page.updated', {
                                date: formatDate(page.updated_at),
                            })}
                        </p>
                    )}
                </div>
            </div>

            <div className="bg-background">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <div className="leading-relaxed whitespace-pre-wrap">
                        {page.body}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
