import { Link } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import type { StoryCard } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = { stories: Paginated<StoryCard> };

export default function Stories({ stories }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.stories.title')}
            description={t('public.stories.subtitle')}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.stories.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.stories.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-5xl space-y-6 px-4 py-10">
                    {stories.data.length === 0 ? (
                        <EmptyState
                            icon={BookOpen}
                            title={t('public.stories.title')}
                            description={t('public.stories.empty')}
                        />
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {stories.data.map((story) => (
                                <Card key={story.slug} className="h-full">
                                    <CardContent className="space-y-3 pt-6">
                                        <div className="flex items-center gap-3">
                                            {story.photo_url ? (
                                                <img
                                                    src={story.photo_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="size-12 rounded-full object-cover"
                                                />
                                            ) : (
                                                <div className="bg-brand-green-100 flex size-12 items-center justify-center rounded-full">
                                                    <BookOpen
                                                        className="text-brand-green-800 size-5"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                            )}

                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {story.author_name}
                                                </p>
                                                {story.career_summary && (
                                                    <p className="text-muted-foreground truncate text-xs">
                                                        {story.career_summary}
                                                    </p>
                                                )}
                                            </div>

                                            {story.is_featured && (
                                                <Badge className="bg-brand-gold-500 text-brand-green-900 ms-auto shrink-0">
                                                    {t(
                                                        'public.stories.featured',
                                                    )}
                                                </Badge>
                                            )}
                                        </div>

                                        <Link
                                            href={story.url}
                                            className="block font-medium hover:underline"
                                        >
                                            {story.title}
                                        </Link>

                                        <p className="text-muted-foreground text-sm">
                                            {story.excerpt}
                                        </p>

                                        {story.batch && (
                                            <p className="text-muted-foreground text-xs">
                                                {story.batch}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}

                    <Pagination meta={stories.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
