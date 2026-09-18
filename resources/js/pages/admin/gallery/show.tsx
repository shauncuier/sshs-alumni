import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Images, Trash2, Upload } from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminAlbum, AdminAlbumImage } from '@/types/content';

type Props = {
    album: AdminAlbum;
    images: AdminAlbumImage[];
    options: { max_upload: number };
};

export default function AdminAlbum({ album, images, options }: Props) {
    const { t, choice } = useTranslation();

    return (
        <AdminLayout title={album.title}>
            <div className="space-y-5">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/admin/gallery">
                        <ArrowLeft
                            className="me-1 size-4 rtl:rotate-180"
                            aria-hidden="true"
                        />
                        {t('admin.gallery.title')}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {album.title}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {choice('admin.gallery.images', album.images_count)}
                        </p>
                    </div>

                    <Badge
                        variant={
                            album.status === 'published'
                                ? 'outline'
                                : 'secondary'
                        }
                    >
                        {album.status_label}
                    </Badge>
                </div>

                <UploadCard albumId={album.id} maxUpload={options.max_upload} />

                {images.length === 0 ? (
                    <EmptyState
                        icon={Images}
                        title={album.title}
                        description={t('admin.gallery.empty')}
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {images.map((image) => (
                            <ImageCard key={image.id} image={image} />
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function UploadCard({
    albumId,
    maxUpload,
}: {
    albumId: number;
    maxUpload: number;
}) {
    const { t } = useTranslation();

    const form = useForm<{ photos: File[] }>({ photos: [] });

    const pick = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData(
            'photos',
            Array.from(event.target.files ?? []).slice(0, maxUpload),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(`/admin/gallery/${albumId}/images`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Card>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-3">
                    <Input
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        onChange={pick}
                    />

                    <p className="text-muted-foreground text-xs">
                        {t('admin.gallery.upload_hint', { max: maxUpload })}
                    </p>

                    <InputError message={form.errors.photos} />

                    <Button
                        type="submit"
                        size="sm"
                        disabled={
                            form.processing || form.data.photos.length === 0
                        }
                    >
                        <Upload className="me-1 size-4" aria-hidden="true" />
                        {t('admin.gallery.upload')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

function ImageCard({ image }: { image: AdminAlbumImage }) {
    const { t } = useTranslation();
    const [caption, setCaption] = useState(image.caption ?? '');

    return (
        <Card className="overflow-hidden">
            <img
                src={image.thumb_url}
                alt={image.caption ?? ''}
                loading="lazy"
                className="h-36 w-full object-cover"
            />

            <CardContent className="space-y-2 pt-4">
                <Input
                    value={caption}
                    placeholder={t('admin.gallery.caption')}
                    aria-label={t('admin.gallery.caption')}
                    onChange={(event) => setCaption(event.target.value)}
                    onBlur={() => {
                        if (caption !== (image.caption ?? '')) {
                            router.put(
                                `/admin/gallery-images/${image.id}`,
                                { caption },
                                { preserveScroll: true },
                            );
                        }
                    }}
                />

                <Button
                    variant="ghost"
                    size="sm"
                    className="text-destructive"
                    onClick={() =>
                        router.delete(`/admin/gallery-images/${image.id}`, {
                            preserveScroll: true,
                        })
                    }
                >
                    <Trash2 className="me-1 size-3.5" aria-hidden="true" />
                    {t('common.actions.delete')}
                </Button>
            </CardContent>
        </Card>
    );
}
