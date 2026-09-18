import { Deferred, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
            <article className="bg-background">
                {article.cover_url && (
                    <img
                        src={article.cover_url}
                        alt=""
                        className="h-64 w-full object-cover sm:h-80"
                    />
                )}

                <div className="mx-auto max-w-3xl px-4 py-10">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/news">
                            <ArrowLeft
                                className="me-1 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.news.back')}
                        </Link>
                    </Button>

                    <h1 className="text-brand-green-900 mt-4 text-3xl font-semibold">
                        {article.title}
                    </h1>

                    <p className="text-muted-foreground mt-2 text-sm">
                        {t('public.news.published_on', {
                            date: formatDate(article.published_at),
                        })}
                        {article.author ? ` · ${article.author}` : ''}
                        {article.category ? ` · ${article.category}` : ''}
                    </p>

                    <div className="mt-6 leading-relaxed whitespace-pre-wrap">
                        {article.body}
                    </div>
                </div>
            </article>

            <Deferred
                data="related"
                fallback={
                    <div className="mx-auto max-w-3xl px-4 pb-10">
                        <Skeleton className="h-28 w-full" />
                    </div>
                }
            >
                <RelatedArticles items={related ?? []} />
            </Deferred>
        </PublicLayout>
    );
}

function RelatedArticles({ items }: { items: NewsCard[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="bg-brand-cream">
            <div className="mx-auto max-w-3xl px-4 py-10">
                <h2 className="text-brand-green-900 mb-4 text-lg font-semibold">
                    {t('public.news.related')}
                </h2>

                <div className="grid gap-4 sm:grid-cols-3">
                    {items.map((item) => (
                        <Card key={item.slug}>
                            <CardContent className="space-y-2 pt-5">
                                {item.category && (
                                    <Badge variant="outline">
                                        {item.category}
                                    </Badge>
                                )}
                                <Link
                                    href={item.url}
                                    className="block text-sm font-medium hover:underline"
                                >
                                    {item.title}
                                </Link>
                                <p className="text-muted-foreground text-xs">
                                    {formatDate(item.published_at)}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
