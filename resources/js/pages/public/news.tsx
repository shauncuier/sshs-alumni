import { Deferred, Link, router } from '@inertiajs/react';
import { ArrowRight, Newspaper } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';
import type { NewsCard } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = {
    news: Paginated<NewsCard>;
    filters: { category: string | null };
    categories: string[];
    featured?: NewsCard | null;
};

export default function News({ news, filters, categories, featured }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.news.title')}
            description={t('public.news.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Newspaper className="size-3.5" />
                        Alumni Bulletin & Updates
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.news.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.news.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <Deferred
                        data="featured"
                        fallback={<Skeleton className="h-64 w-full rounded-3xl" />}
                    >
                        {featured ? (
                            <FeaturedArticle article={featured} />
                        ) : (
                            <></>
                        )}
                    </Deferred>

                    {categories.length > 0 && (
                        <div className="flex flex-wrap gap-2 pt-2">
                            <Button
                                size="sm"
                                variant={filters.category ? 'outline' : 'default'}
                                className={`rounded-full px-4 py-1.5 text-xs font-semibold transition ${
                                    !filters.category
                                        ? 'bg-teal-600 text-white hover:bg-teal-700 shadow-sm shadow-teal-600/20'
                                        : 'border-teal-500/20 text-slate-700 hover:bg-teal-50 hover:text-teal-900'
                                }`}
                                onClick={() => router.get('/news')}
                            >
                                {t('public.news.all_categories')}
                            </Button>

                            {categories.map((category) => (
                                <Button
                                    key={category}
                                    size="sm"
                                    variant={
                                        filters.category === category
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className={`rounded-full px-4 py-1.5 text-xs font-semibold transition ${
                                        filters.category === category
                                            ? 'bg-teal-600 text-white hover:bg-teal-700 shadow-sm shadow-teal-600/20'
                                            : 'border-teal-500/20 text-slate-700 hover:bg-teal-50 hover:text-teal-900'
                                    }`}
                                    onClick={() =>
                                        router.get('/news', { category })
                                    }
                                >
                                    {category}
                                </Button>
                            ))}
                        </div>
                    )}

                    {news.data.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={Newspaper}
                                title={t('public.news.title')}
                                description={
                                    filters.category
                                        ? t('public.news.empty_filtered')
                                        : t('public.news.empty')
                                }
                            />
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {news.data.map((item) => (
                                <ArticleCard key={item.slug} article={item} />
                            ))}
                        </div>
                    )}

                    <Pagination meta={news.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}

function FeaturedArticle({ article }: { article: NewsCard }) {
    const { t } = useTranslation();

    return (
        <div className="group relative overflow-hidden rounded-3xl border border-teal-500/20 bg-white/95 shadow-md backdrop-blur-md transition-all duration-300 hover:border-teal-500/40 hover:shadow-xl">
            <div className="grid sm:grid-cols-2">
                {article.cover_url ? (
                    <div className="relative h-60 w-full overflow-hidden bg-slate-100 sm:h-full min-h-[220px]">
                        <img
                            src={article.cover_url}
                            alt=""
                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                    </div>
                ) : (
                    <div className="hidden bg-gradient-to-br from-teal-900/10 via-teal-800/5 to-cyan-900/10 sm:block" />
                )}

                <div className="flex flex-col justify-between space-y-4 p-6 sm:p-8">
                    <div className="space-y-3">
                        <span className="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 border border-amber-300/70">
                            {t('public.news.featured')}
                        </span>

                        <Link
                            href={article.url}
                            className="block text-xl sm:text-2xl font-bold text-slate-900 group-hover:text-teal-900 transition"
                        >
                            {article.title}
                        </Link>

                        {article.excerpt && (
                            <p className="line-clamp-3 text-sm text-slate-600 leading-relaxed">
                                {article.excerpt}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-between border-t border-slate-100 pt-4">
                        <p className="text-xs font-medium text-slate-400">
                            {t('public.news.published_on', {
                                date: formatDate(article.published_at),
                            })}
                        </p>
                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 transition-transform group-hover:translate-x-1">
                            Read story <ArrowRight className="size-3.5" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ArticleCard({ article }: { article: NewsCard }) {
    return (
        <div className="group flex flex-col overflow-hidden rounded-3xl border border-teal-500/15 bg-white/90 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-lg">
            {article.cover_url && (
                <div className="relative h-44 w-full overflow-hidden bg-slate-100">
                    <img
                        src={article.cover_url}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                </div>
            )}

            <div className="flex flex-1 flex-col justify-between space-y-3 p-5">
                <div className="space-y-2">
                    {article.category && (
                        <span className="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 border border-teal-200/60">
                            {article.category}
                        </span>
                    )}

                    <Link
                        href={article.url}
                        className="block font-bold text-slate-900 group-hover:text-teal-900 transition line-clamp-2"
                    >
                        {article.title}
                    </Link>

                    {article.excerpt && (
                        <p className="line-clamp-2 text-xs text-slate-500 leading-relaxed">
                            {article.excerpt}
                        </p>
                    )}
                </div>

                <div className="border-t border-slate-100 pt-3">
                    <p className="text-[11px] font-medium text-slate-400">
                        {formatDate(article.published_at)}
                    </p>
                </div>
            </div>
        </div>
    );
}
