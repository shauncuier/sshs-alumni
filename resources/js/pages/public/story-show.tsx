import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';
import type { Story } from '@/types/content';

type Props = { story: Story };

/**
 * One alumnus's account of their own life.
 *
 * Rendered as TEXT with preserved line breaks — a member wrote it in a
 * textarea, and nothing they typed becomes markup.
 */
export default function StoryShow({ story }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={story.title}
            description={story.meta_description ?? undefined}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-white hover:bg-white/10 hover:text-white"
                        asChild
                    >
                        <Link href="/stories">
                            <ArrowLeft
                                className="me-1 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.stories.back')}
                        </Link>
                    </Button>

                    <div className="mt-5 flex items-center gap-4">
                        {story.photo_url && (
                            <img
                                src={story.photo_url}
                                alt=""
                                className="size-16 rounded-full object-cover"
                            />
                        )}

                        <div>
                            <h1 className="text-2xl font-semibold">
                                {story.title}
                            </h1>
                            <p className="mt-1 text-white/70">
                                {t('public.stories.by', {
                                    name: story.author_name,
                                })}
                                {story.batch
                                    ? ` · ${story.batch}`
                                    : ''}
                            </p>
                            {story.career_summary && (
                                <p className="text-brand-gold-500 mt-1 text-sm">
                                    {story.career_summary}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-background">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <div className="leading-relaxed whitespace-pre-wrap">
                        {story.body}
                    </div>

                    {story.published_at && (
                        <p className="text-muted-foreground mt-8 text-xs">
                            {formatDate(story.published_at)}
                        </p>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
