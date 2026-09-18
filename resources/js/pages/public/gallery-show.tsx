import { Link } from '@inertiajs/react';
import { ArrowLeft, Images } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-10">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-white hover:bg-white/10 hover:text-white"
                        asChild
                    >
                        <Link href="/gallery">
                            <ArrowLeft
                                className="me-1 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.gallery.back')}
                        </Link>
                    </Button>

                    <h1 className="mt-3 text-3xl font-semibold">
                        {album.title}
                    </h1>

                    {album.description && (
                        <p className="mt-2 max-w-prose text-white/70">
                            {album.description}
                        </p>
                    )}

                    <div className="mt-4 flex flex-wrap items-center gap-2 text-sm text-white/70">
                        <span>
                            {choice(
                                'public.gallery.photos',
                                album.images_count,
                            )}
                        </span>
                        {album.published_at && (
                            <span>· {formatDate(album.published_at)}</span>
                        )}
                        {album.event && (
                            <Badge
                                variant="outline"
                                className="border-white/40 text-white"
                            >
                                {album.event}
                            </Badge>
                        )}
                        {album.batch && (
                            <Badge
                                variant="outline"
                                className="border-white/40 text-white"
                            >
                                {album.batch}
                            </Badge>
                        )}
                    </div>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-5xl px-4 py-10">
                    {images.length === 0 ? (
                        <EmptyState
                            icon={Images}
                            title={album.title}
                            description={t('public.gallery.album_empty')}
                        />
                    ) : (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            {images.map((image) => (
                                <a
                                    key={image.id}
                                    href={image.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="group overflow-hidden rounded-md border bg-white"
                                    aria-label={
                                        image.caption ??
                                        t('public.gallery.open_full')
                                    }
                                >
                                    <img
                                        src={image.thumb_url}
                                        alt={image.caption ?? ''}
                                        loading="lazy"
                                        className="h-40 w-full object-cover transition-transform group-hover:scale-105"
                                    />

                                    {image.caption && (
                                        <p className="text-muted-foreground px-2 py-1.5 text-xs">
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
