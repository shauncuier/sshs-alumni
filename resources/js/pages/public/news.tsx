import { Deferred, Link, router } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.news.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.news.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-5xl space-y-8 px-4 py-10">
                    <Deferred
                        data="featured"
                        fallback={<Skeleton className="h-52 w-full" />}
                    >
                        {featured ? (
                            <FeaturedArticle article={featured} />
                        ) : (
                            <></>
                        )}
                    </Deferred>

                    {categories.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant={
                                    filters.category ? 'outline' : 'default'
                                }
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
                        <EmptyState
                            icon={Newspaper}
                            title={t('public.news.title')}
                            description={
                                filters.category
                                    ? t('public.news.empty_filtered')
                                    : t('public.news.empty')
                            }
                        />
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
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
        <Card className="overflow-hidden">
            <div className="grid sm:grid-cols-2">
                {article.cover_url ? (
                    <img
                        src={article.cover_url}
                        alt=""
                        className="h-52 w-full object-cover sm:h-full"
                    />
                ) : (
                    <div className="bg-brand-green-100 hidden sm:block" />
                )}

                <CardContent className="space-y-3 py-6">
                    <Badge className="bg-brand-gold-500 text-brand-green-900">
                        {t('public.news.featured')}
                    </Badge>

                    <Link
                        href={article.url}
                        className="block text-xl font-semibold hover:underline"
                    >
                        {article.title}
                    </Link>

                    {article.excerpt && (
                        <p className="text-muted-foreground text-sm">
                            {article.excerpt}
                        </p>
                    )}

                    <p className="text-muted-foreground text-xs">
                        {t('public.news.published_on', {
                            date: formatDate(article.published_at),
                        })}
                    </p>
                </CardContent>
            </div>
        </Card>
    );
}

function ArticleCard({ article }: { article: NewsCard }) {
    return (
        <Card className="overflow-hidden">
            {article.cover_url && (
                <img
                    src={article.cover_url}
                    alt=""
                    loading="lazy"
                    className="h-40 w-full object-cover"
                />
            )}

            <CardContent className="space-y-2 pt-5">
                {article.category && (
                    <Badge variant="outline">{article.category}</Badge>
                )}

                <Link
                    href={article.url}
                    className="block font-medium hover:underline"
                >
                    {article.title}
                </Link>

                {article.excerpt && (
                    <p className="text-muted-foreground text-sm">
                        {article.excerpt}
                    </p>
                )}

                <p className="text-muted-foreground text-xs">
                    {formatDate(article.published_at)}
                </p>
            </CardContent>
        </Card>
    );
}
