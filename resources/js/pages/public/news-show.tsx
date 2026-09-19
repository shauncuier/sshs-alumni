import { Deferred, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Calendar, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';
import type { NewsArticle, NewsCard } from '@/types/content';

type Props = {
    article: NewsArticle;
    related?: NewsCard[];
};

/**
 * One article.
 *
 * The body is rendered as TEXT with preserved line breaks, not as HTML. It is
 * written by a committee member in a textarea, and treating it as markup would
 * mean a stray angle bracket silently eats the rest of the paragraph — and
 * would put a stored-XSS hole one compromised editor account away.
 */
export default function NewsShow({ article, related }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={article.meta_title ?? article.title}
            description={article.meta_description ?? undefined}
        >
            {/* Ambient Hero / Header */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-14 sm:py-18 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Button
                        variant="ghost"
                        size="sm"
                        asChild
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                    >
                        <Link href="/news">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.news.back')}
                        </Link>
                    </Button>

                    {article.category && (
                        <div>
                            <span className="inline-flex items-center rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                                {article.category}
                            </span>
                        </div>
                    )}

                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl leading-tight">
                        {article.title}
                    </h1>

                    <div className="mt-5 flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-300">
                        <span className="inline-flex items-center gap-1.5">
                            <Calendar className="size-4 text-teal-400" />
                            {t('public.news.published_on', {
                                date: formatDate(article.published_at),
                            })}
                        </span>
                        {article.author && (
                            <span className="inline-flex items-center gap-1.5">
                                <User className="size-4 text-cyan-400" />
                                {article.author}
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Main Reading Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <article className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {article.cover_url && (
                        <div className="mb-8 overflow-hidden rounded-3xl border border-teal-500/20 shadow-lg">
                            <img
                                src={article.cover_url}
                                alt=""
                                className="h-72 w-full object-cover sm:h-96"
                            />
                        </div>
                    )}

                    <div className="glass-panel-light rounded-3xl p-6 sm:p-10 border border-teal-500/15 shadow-sm">
                        <div className="prose prose-slate max-w-none text-base sm:text-lg leading-relaxed text-slate-700 whitespace-pre-wrap">
                            {article.body}
                        </div>
                    </div>
                </article>

                <Deferred
                    data="related"
                    fallback={
                        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 pt-12">
                            <Skeleton className="h-32 w-full rounded-3xl" />
                        </div>
                    }
                >
                    <RelatedArticles items={related ?? []} />
                </Deferred>
            </div>
        </PublicLayout>
    );
}

function RelatedArticles({ items }: { items: NewsCard[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16">
            <h2 className="text-xl font-bold text-slate-900 mb-6">
                {t('public.news.related')}
            </h2>

            <div className="grid gap-4 sm:grid-cols-3">
                {items.map((item) => (
                    <div
                        key={item.slug}
                        className="group flex flex-col justify-between rounded-2xl border border-teal-500/15 bg-white/90 p-5 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-md"
                    >
                        <div className="space-y-2">
                            {item.category && (
                                <span className="inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700 border border-teal-200/60">
                                    {item.category}
                                </span>
                            )}
                            <Link
                                href={item.url}
                                className="block font-bold text-slate-900 group-hover:text-teal-900 transition line-clamp-2 text-sm"
                            >
                                {item.title}
                            </Link>
                        </div>
                        <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-400">
                            <span>{formatDate(item.published_at)}</span>
                            <ArrowRight className="size-3 text-teal-600 transition-transform group-hover:translate-x-1" />
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
