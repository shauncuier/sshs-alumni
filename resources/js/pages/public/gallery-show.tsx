import { Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Images } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';
import type { AlbumImage } from '@/types/content';

type Album = {
    slug: string;
    title: string;
    description: string | null;
    event: string | null;
    batch: string | null;
    published_at: string | null;
    images_count: number;
};

type Props = {
    album: Album;
    images: AlbumImage[];
};

/**
 * One album.
 *
 * Each photograph links to its full-size file rather than opening a lightbox.
 * A plain link works with a right-click, on a slow connection, and for
 * somebody who wants to save the picture of their own class — which is most of
 * why these are here.
 */
export default function GalleryShow({ album, images }: Props) {
    const { t, choice } = useTranslation();

    return (
        <PublicLayout
            title={album.title}
            description={album.description ?? undefined}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-14 sm:py-18 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                        asChild
                    >
                        <Link href="/gallery">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.gallery.back')}
                        </Link>
                    </Button>

                    <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {album.title}
                    </h1>

                    {album.description && (
                        <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300 leading-relaxed">
                            {album.description}
                        </p>
                    )}

                    <div className="mt-5 flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-300">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-teal-400/30 bg-teal-500/10 px-3 py-1 text-xs font-semibold text-teal-300 backdrop-blur-md">
                            <Images className="size-3.5" />
                            {choice(
                                'public.gallery.photos',
                                album.images_count,
                            )}
                        </span>
                        {album.published_at && (
                            <span className="text-slate-400">
                                {formatDate(album.published_at)}
                            </span>
                        )}
                        {album.event && (
                            <span className="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-xs text-slate-300">
                                {album.event}
                            </span>
                        )}
                        {album.batch && (
                            <span className="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-xs text-slate-300">
                                {album.batch}
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    {images.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={Images}
                                title={album.title}
                                description={t('public.gallery.album_empty')}
                            />
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            {images.map((image) => (
                                <a
                                    key={image.id}
                                    href={image.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="group relative flex flex-col overflow-hidden rounded-2xl border border-teal-500/15 bg-white shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-teal-500/35 hover:shadow-xl"
                                    aria-label={
                                        image.caption ??
                                        t('public.gallery.open_full')
                                    }
                                >
                                    <div className="relative h-48 w-full overflow-hidden bg-slate-100">
                                        <img
                                            src={image.thumb_url}
                                            alt={image.caption ?? ''}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        />
                                        <div className="absolute inset-0 flex items-center justify-center bg-slate-950/20 opacity-0 backdrop-blur-xs transition-opacity duration-300 group-hover:opacity-100">
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-900 shadow-md">
                                                <span>View Full</span>
                                                <ExternalLink className="size-3" />
                                            </span>
                                        </div>
                                    </div>

                                    {image.caption && (
                                        <p className="line-clamp-2 p-3 text-xs text-slate-600">
                                            {image.caption}
                                        </p>
                                    )}
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
