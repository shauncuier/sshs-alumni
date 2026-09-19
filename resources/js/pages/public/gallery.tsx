import { Link } from '@inertiajs/react';
import { ArrowRight, Images } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import type { AlbumCard } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = { albums: Paginated<AlbumCard> };

export default function Gallery({ albums }: Props) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout
            title={t('public.gallery.title')}
            description={t('public.gallery.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Images className="size-3.5" />
                        Moments & Archives
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.gallery.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.gallery.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                    {albums.data.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={Images}
                                title={t('public.gallery.title')}
                                description={t('public.gallery.empty')}
                            />
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {albums.data.map((album) => (
                                <Link
                                    key={album.slug}
                                    href={album.url}
                                    className="group flex flex-col overflow-hidden rounded-3xl border border-teal-500/15 bg-white/90 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-xl"
                                >
                                    {album.cover_url ? (
                                        <div className="relative h-48 w-full overflow-hidden bg-slate-100">
                                            <img
                                                src={album.cover_url}
                                                alt=""
                                                loading="lazy"
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            />
                                        </div>
                                    ) : (
                                        <div className="flex h-48 items-center justify-center bg-gradient-to-br from-teal-900/10 via-teal-800/5 to-cyan-900/10">
                                            <Images
                                                className="size-10 text-teal-700/60"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    )}

                                    <div className="flex flex-1 flex-col justify-between space-y-3 p-5">
                                        <div className="space-y-2">
                                            <p className="font-bold text-slate-900 group-hover:text-teal-900 transition line-clamp-1">
                                                {album.title}
                                            </p>

                                            <p className="text-xs text-slate-500">
                                                {choice(
                                                    'public.gallery.photos',
                                                    album.images_count,
                                                )}
                                            </p>

                                            <div className="flex flex-wrap gap-1.5 pt-1">
                                                {album.event && (
                                                    <span className="inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700 border border-teal-200/60">
                                                        {album.event}
                                                    </span>
                                                )}
                                                {album.batch && (
                                                    <span className="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                        {album.batch}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-end border-t border-slate-100 pt-3">
                                            <span className="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 transition-transform group-hover:translate-x-1">
                                                View album <ArrowRight className="size-3" />
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}

                    <Pagination meta={albums.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
