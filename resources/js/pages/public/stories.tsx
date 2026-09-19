import { Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, GraduationCap } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <BookOpen className="size-3.5" />
                        Journeys & Voices
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.stories.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.stories.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {stories.data.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={BookOpen}
                                title={t('public.stories.title')}
                                description={t('public.stories.empty')}
                            />
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {stories.data.map((story) => (
                                <div
                                    key={story.slug}
                                    className="group flex flex-col justify-between overflow-hidden rounded-3xl border border-teal-500/15 bg-white/90 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-xl"
                                >
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-3.5">
                                            {story.photo_url ? (
                                                <img
                                                    src={story.photo_url}
                                                    alt=""
                                                    loading="lazy"
                                                    className="size-13 rounded-full object-cover ring-2 ring-teal-500/20 group-hover:ring-teal-500/40 transition"
                                                />
                                            ) : (
                                                <div className="flex size-13 items-center justify-center rounded-full bg-teal-50 text-teal-700 ring-2 ring-teal-500/20">
                                                    <BookOpen
                                                        className="size-5"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                            )}

                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-bold text-slate-900 group-hover:text-teal-900 transition">
                                                    {story.author_name}
                                                </p>
                                                {story.career_summary && (
                                                    <p className="truncate text-xs text-slate-500">
                                                        {story.career_summary}
                                                    </p>
                                                )}
                                            </div>

                                            {story.is_featured && (
                                                <span className="inline-flex shrink-0 items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 border border-amber-300/70">
                                                    {t(
                                                        'public.stories.featured',
                                                    )}
                                                </span>
                                            )}
                                        </div>

                                        <Link
                                            href={story.url}
                                            className="block font-bold text-slate-900 group-hover:text-teal-900 transition line-clamp-2"
                                        >
                                            {story.title}
                                        </Link>

                                        <p className="line-clamp-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                            {story.excerpt}
                                        </p>
                                    </div>

                                    <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                        {story.batch ? (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                                <GraduationCap className="size-3 text-slate-500" />
                                                {story.batch}
                                            </span>
                                        ) : (
                                            <span />
                                        )}

                                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 transition-transform group-hover:translate-x-1">
                                            Read <ArrowRight className="size-3.5" />
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <Pagination meta={stories.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
