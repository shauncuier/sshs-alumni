import { Link, router, useForm } from '@inertiajs/react';
import { Images, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminAlbum, Option } from '@/types/content';

type Options = {
    statuses: Option[];
    max_upload: number;
    events: Option[];
    batches: Option[];
};

type Props = {
    albums: AdminAlbum[];
    options: Options;
    can: { manage: boolean; publish: boolean };
};

export default function AdminGallery({ albums, options, can }: Props) {
    const { t, choice } = useTranslation();
    const [editing, setEditing] = useState<AdminAlbum | null>(null);

    return (
        <AdminLayout title={t('admin.gallery.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.gallery.title')}
                    </h1>

                    {can.manage && <AlbumDialog options={options} />}
                </div>

                {albums.length === 0 ? (
                    <EmptyState
                        icon={Images}
                        title={t('admin.gallery.title')}
                        description={t('admin.gallery.empty')}
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {albums.map((album) => (
                            <Card key={album.id} className="overflow-hidden">
                                {album.cover_url && (
                                    <img
                                        src={album.cover_url}
                                        alt=""
                                        className="h-32 w-full object-cover"
                                    />
                                )}

                                <CardContent className="space-y-2 pt-5">
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="font-medium">
                                            {album.title}
                                        </p>
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

                                    <p className="text-muted-foreground text-xs">
                                        {choice(
                                            'admin.gallery.images',
                                            album.images_count,
                                        )}
                                        {album.event ? ` · ${album.event}` : ''}
                                        {album.batch ? ` · ${album.batch}` : ''}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/admin/gallery/${album.id}`}
                                            >
                                                {t('admin.gallery.open')}
                                            </Link>
                                        </Button>

                                        {can.manage && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditing(album)
                                                }
                                            >
                                                <Pencil
                                                    className="me-1 size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t('common.actions.edit')}
                                            </Button>
                                        )}

                                        {can.publish && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.put(
                                                        `/admin/gallery/${album.id}/publish`,
                                                        {
                                                            publish:
                                                                album.status !==
                                                                'published',
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {album.status === 'published'
                                                    ? t(
                                                          'admin.content.unpublish',
                                                      )
                                                    : t(
                                                          'admin.content.publish',
                                                      )}
                                            </Button>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {editing && (
                <AlbumDialog
                    options={options}
                    album={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminLayout>
    );
}

function AlbumDialog({
    options,
    album,
    onClose,
}: {
    options: Options;
    album?: AdminAlbum;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(album !== undefined);

    const form = useForm({
        title: album?.title ?? '',
        description: album?.description ?? '',
        event_id: album?.event_id ? String(album.event_id) : '',
        batch_id: album?.batch_id ? String(album.batch_id) : '',
        display_order: album?.display_order ?? 0,
    });

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const opts = {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                close();
            },
        };

        if (album) {
            form.put(`/admin/gallery/${album.id}`, opts);
        } else {
            form.post('/admin/gallery', opts);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!album && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.gallery.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.gallery.create')}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="title">{t('common.labels.name')}</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="description">
                            {t('common.labels.description')}
                        </Label>
                        <textarea
                            id="description"
                            rows={3}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="event_id">
                                {t('admin.gallery.tied_event')}
                            </Label>
                            <Select
                                value={form.data.event_id || 'none'}
                                onValueChange={(value) =>
                                    form.setData(
                                        'event_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="event_id">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">—</SelectItem>
                                    {options.events.map((event) => (
                                        <SelectItem
                                            key={event.value}
                                            value={event.value}
                                        >
                                            {event.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="batch_id">
                                {t('admin.gallery.tied_batch')}
                            </Label>
                            <Select
                                value={form.data.batch_id || 'none'}
                                onValueChange={(value) =>
                                    form.setData(
                                        'batch_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="batch_id">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">—</SelectItem>
                                    {options.batches.map((batch) => (
                                        <SelectItem
                                            key={batch.value}
                                            value={batch.value}
                                        >
                                            {batch.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
