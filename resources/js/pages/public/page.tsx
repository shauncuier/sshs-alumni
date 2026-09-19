import { FileText } from 'lucide-react';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <FileText className="size-3.5" />
                        Institutional Document
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {page.title}
                    </h1>

                    {page.updated_at && (
                        <p className="mt-3 text-xs sm:text-sm text-slate-300">
                            {t('public.page.updated', {
                                date: formatDate(page.updated_at),
                            })}
                        </p>
                    )}
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="glass-panel-light rounded-3xl p-6 sm:p-12 border border-teal-500/15 shadow-sm">
                        <div className="prose prose-slate max-w-none text-base sm:text-lg leading-relaxed text-slate-700 whitespace-pre-wrap">
                            {page.body}
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
