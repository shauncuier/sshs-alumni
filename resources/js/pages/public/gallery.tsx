import { Link } from '@inertiajs/react';
import { Images } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.gallery.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.gallery.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-5xl space-y-6 px-4 py-10">
                    {albums.data.length === 0 ? (
                        <EmptyState
                            icon={Images}
                            title={t('public.gallery.title')}
                            description={t('public.gallery.empty')}
                        />
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {albums.data.map((album) => (
                                <Link
                                    key={album.slug}
                                    href={album.url}
                                    className="group"
                                >
                                    <Card className="h-full overflow-hidden">
                                        {album.cover_url ? (
                                            <img
                                                src={album.cover_url}
                                                alt=""
                                                loading="lazy"
                                                className="h-44 w-full object-cover transition-transform group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="bg-brand-green-100 flex h-44 items-center justify-center">
                                                <Images
                                                    className="text-brand-green-800 size-8"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        )}

                                        <CardContent className="space-y-2 pt-5">
                                            <p className="font-medium group-hover:underline">
                                                {album.title}
                                            </p>

                                            <p className="text-muted-foreground text-xs">
                                                {choice(
                                                    'public.gallery.photos',
                                                    album.images_count,
                                                )}
                                            </p>

                                            <div className="flex flex-wrap gap-1">
                                                {album.event && (
                                                    <Badge variant="outline">
                                                        {album.event}
                                                    </Badge>
                                                )}
                                                {album.batch && (
                                                    <Badge variant="outline">
                                                        {album.batch}
                                                    </Badge>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>
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
