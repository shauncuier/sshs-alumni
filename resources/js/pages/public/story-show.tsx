import { Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Calendar, GraduationCap } from 'lucide-react';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-14 sm:py-18 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                        asChild
                    >
                        <Link href="/stories">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.stories.back')}
                        </Link>
                    </Button>

                    <div className="flex flex-col sm:flex-row sm:items-center gap-5">
                        {story.photo_url ? (
                            <img
                                src={story.photo_url}
                                alt=""
                                className="size-20 sm:size-24 rounded-2xl object-cover ring-4 ring-teal-500/20 shadow-xl"
                            />
                        ) : (
                            <div className="flex size-20 sm:size-24 items-center justify-center rounded-2xl bg-teal-500/15 text-teal-300 ring-4 ring-teal-500/20 shadow-xl backdrop-blur-md">
                                <BookOpen className="size-8" aria-hidden="true" />
                            </div>
                        )}

                        <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1 rounded-full border border-teal-400/30 bg-teal-500/10 px-3 py-1 text-xs font-semibold text-teal-300 backdrop-blur-md">
                                    {t('public.stories.by', {
                                        name: story.author_name,
                                    })}
                                </span>
                                {story.batch && (
                                    <span className="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-xs text-slate-300 backdrop-blur-md">
                                        <GraduationCap className="size-3" />
                                        {story.batch}
                                    </span>
                                )}
                            </div>

                            <h1 className="mt-3 text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-tight">
                                {story.title}
                            </h1>

                            {story.career_summary && (
                                <p className="mt-2 text-sm sm:text-base font-medium text-amber-300">
                                    {story.career_summary}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Reading Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="glass-panel-light rounded-3xl p-6 sm:p-10 border border-teal-500/15 shadow-sm">
                        <div className="prose prose-slate max-w-none text-base sm:text-lg leading-relaxed text-slate-700 whitespace-pre-wrap">
                            {story.body}
                        </div>

                        {story.published_at && (
                            <div className="mt-10 flex items-center gap-2 border-t border-slate-100 pt-6 text-xs text-slate-400">
                                <Calendar className="size-3.5 text-teal-600" />
                                <span>{formatDate(story.published_at)}</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
