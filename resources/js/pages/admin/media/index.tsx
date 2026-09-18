import { router, useForm } from '@inertiajs/react';
import { FileImage, Lock, Search, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate, formatNumber } from '@/lib/format';
import type { MediaItem, Option } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = {
    media: Paginated<MediaItem>;
    filters: { collection: string | null; q: string | null };
    options: { collections: Option[] };
    totals: { size: number; count: number };
    can: { manage: boolean };
};

/**
 * The media library.
 *
 * Private files are listed without a link. They are served through a
 * permission check, never by URL, and a row that showed a link would either
 * 404 or — worse — work.
 */
export default function AdminMedia({
    media,
    filters,
    options,
    totals,
    can,
}: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.q ?? '');

    return (
        <AdminLayout title={t('admin.media.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t('admin.media.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('admin.media.total', {
                                files: formatNumber(totals.count),
                                size: formatBytes(totals.size),
                            })}
                        </p>
                    </div>

                    {can.manage && <UploadForm />}
                </div>

                <div className="flex flex-wrap gap-2">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            router.get(
                                '/admin/media',
                                search === '' ? {} : { q: search },
                                { preserveState: true, replace: true },
                            );
                        }}
                        className="flex gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="w-56"
                            aria-label={t('common.actions.search')}
                        />
                        <Button type="submit" variant="secondary" size="icon">
                            <Search className="size-4" aria-hidden="true" />
                            <span className="sr-only">
                                {t('common.actions.search')}
                            </span>
                        </Button>
                    </form>

                    <Select
                        value={filters.collection ?? 'all'}
                        onValueChange={(value) =>
                            router.get(
                                '/admin/media',
                                value === 'all' ? {} : { collection: value },
                                { preserveState: true, replace: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('admin.community.filter_all')}
                            </SelectItem>
                            {options.collections.map((collection) => (
                                <SelectItem
                                    key={collection.value}
                                    value={collection.value}
                                >
                                    {collection.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {media.data.length === 0 ? (
                    <EmptyState
                        icon={FileImage}
                        title={t('admin.media.title')}
                        description={t('admin.media.empty')}
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {media.data.map((item) => (
                            <Card key={item.id} className="overflow-hidden">
                                {item.thumb_url ? (
                                    <img
                                        src={item.thumb_url}
                                        alt={item.original_name}
                                        loading="lazy"
                                        className="h-32 w-full object-cover"
                                    />
                                ) : (
                                    <div className="bg-muted flex h-32 items-center justify-center">
                                        <Lock
                                            className="text-muted-foreground size-6"
                                            aria-hidden="true"
                                        />
                                    </div>
                                )}

                                <CardContent className="space-y-2 pt-4">
                                    <p
                                        className="truncate text-sm font-medium"
                                        title={item.original_name}
                                    >
                                        {item.original_name}
                                    </p>

                                    <p className="text-muted-foreground text-xs">
                                        {item.collection_label}
                                        {' · '}
                                        {formatBytes(item.size)}
                                        {item.width && item.height
                                            ? ` · ${t('admin.media.dimensions', { width: item.width, height: item.height })}`
                                            : ''}
                                    </p>

                                    <p className="text-muted-foreground text-xs">
                                        {item.uploaded_by}
                                        {item.created_at
                                            ? ` · ${formatDate(item.created_at)}`
                                            : ''}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1">
                                        {!item.is_public && (
                                            <Badge
                                                variant="secondary"
                                                className="gap-1"
                                            >
                                                <Lock
                                                    className="size-3"
                                                    aria-hidden="true"
                                                />
                                                {t('admin.media.private')}
                                            </Badge>
                                        )}

                                        {item.in_use && (
                                            <Badge variant="outline">
                                                {t('admin.media.in_use_badge')}
                                            </Badge>
                                        )}
                                    </div>

                                    {can.manage && !item.in_use && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() =>
                                                router.delete(
                                                    `/admin/media/${item.id}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2
                                                className="me-1 size-3.5"
                                                aria-hidden="true"
                                            />
                                            {t('common.actions.delete')}
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={media.meta} />
            </div>
        </AdminLayout>
    );
}

function UploadForm() {
    const { t } = useTranslation();
    const form = useForm<{ file: File | null }>({ file: null });

    const pick = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData('file', event.target.files?.[0] ?? null);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/admin/media', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={submit} className="flex items-center gap-2">
            <Input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={pick}
                className="w-56"
                aria-label={t('admin.media.upload')}
            />
            <Button
                type="submit"
                size="sm"
                disabled={form.processing || form.data.file === null}
            >
                <Upload className="me-1 size-4" aria-hidden="true" />
                {t('admin.media.upload')}
            </Button>
            <InputError message={form.errors.file} />
        </form>
    );
}

/**
 * Bytes as something a person can read. Binary units, because that is what a
 * host's disk quota is quoted in.
 */
function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(1)} ${units[unit]}`;
}
