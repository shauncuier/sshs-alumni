import { router, useForm } from '@inertiajs/react';
import { Megaphone, Pencil, Pin, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { formatDateTime } from '@/lib/format';
import type { AdminAnnouncement, Option } from '@/types/content';
import type { Paginated } from '@/types/member';

type Options = {
    kinds: Option[];
    levels: Option[];
    audiences: Option[];
    statuses: Option[];
    batches: Option[];
};

type Props = {
    announcements: Paginated<AdminAnnouncement>;
    filters: { kind: string | null; status: string | null };
    options: Options;
    can: { manage: boolean; publish: boolean };
};

export default function AdminAnnouncements({
    announcements,
    filters,
    options,
    can,
}: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<AdminAnnouncement | null>(null);

    return (
        <AdminLayout title={t('admin.announcements.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.announcements.title')}
                    </h1>

                    {can.manage && <AnnouncementDialog options={options} />}
                </div>

                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(value) =>
                        router.get(
                            '/admin/announcements',
                            value === 'all' ? {} : { status: value },
                            { preserveState: true, replace: true },
                        )
                    }
                >
                    <SelectTrigger className="w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">
                            {t('admin.community.filter_status')}
                        </SelectItem>
                        {options.statuses.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {announcements.data.length === 0 ? (
                    <EmptyState
                        icon={Megaphone}
                        title={t('admin.announcements.title')}
                        description={t('admin.announcements.empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        {announcements.data.map((announcement) => (
                            <Card key={announcement.id}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {announcement.title}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {announcement.audience_label}
                                                {announcement.batch
                                                    ? ` · ${announcement.batch}`
                                                    : ''}
                                                {announcement.starts_at
                                                    ? ` · ${formatDateTime(announcement.starts_at)}`
                                                    : ''}
                                                {announcement.ends_at
                                                    ? ` → ${formatDateTime(announcement.ends_at)}`
                                                    : ''}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-1">
                                            {announcement.is_pinned && (
                                                <Badge
                                                    variant="secondary"
                                                    className="gap-1"
                                                >
                                                    <Pin
                                                        className="size-3"
                                                        aria-hidden="true"
                                                    />
                                                </Badge>
                                            )}
                                            <Badge variant="outline">
                                                {announcement.kind_label}
                                            </Badge>
                                            {announcement.level !== 'info' && (
                                                <Badge
                                                    variant={
                                                        announcement.level ===
                                                        'urgent'
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {announcement.level_label}
                                                </Badge>
                                            )}
                                            {/* Status is not the same as
                                                visible: a published notice
                                                whose window has closed is off
                                                the site. */}
                                            <Badge
                                                variant={
                                                    announcement.is_live
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {announcement.is_live
                                                    ? t('admin.content.live')
                                                    : announcement.status_label}
                                            </Badge>
                                        </div>
                                    </div>

                                    <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                                        {announcement.body}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        {can.manage && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditing(announcement)
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
                                                        `/admin/announcements/${announcement.id}/publish`,
                                                        {
                                                            publish:
                                                                announcement.status !==
                                                                'published',
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {announcement.status ===
                                                'published'
                                                    ? t(
                                                          'admin.content.unpublish',
                                                      )
                                                    : t(
                                                          'admin.content.publish',
                                                      )}
                                            </Button>
                                        )}

                                        {can.manage && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    router.delete(
                                                        `/admin/announcements/${announcement.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
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
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={announcements.meta} />
            </div>

            {editing && (
                <AnnouncementDialog
                    options={options}
                    announcement={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminLayout>
    );
}

function AnnouncementDialog({
    options,
    announcement,
    onClose,
}: {
    options: Options;
    announcement?: AdminAnnouncement;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(announcement !== undefined);

    const form = useForm({
        kind: announcement?.kind ?? 'announcement',
        title: announcement?.title ?? '',
        body: announcement?.body ?? '',
        level: announcement?.level ?? 'info',
        audience: announcement?.audience ?? 'public',
        batch_id: announcement?.batch_id ? String(announcement.batch_id) : '',
        starts_at: announcement?.starts_at?.slice(0, 16) ?? '',
        ends_at: announcement?.ends_at?.slice(0, 16) ?? '',
        is_pinned: announcement?.is_pinned ?? false,
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

        if (announcement) {
            form.put(`/admin/announcements/${announcement.id}`, opts);
        } else {
            form.post('/admin/announcements', opts);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!announcement && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.announcements.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t('admin.announcements.create')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('admin.announcements.window_hint')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Choice
                            id="kind"
                            label={t('admin.announcements.kind')}
                            value={form.data.kind}
                            options={options.kinds}
                            onChange={(value) => form.setData('kind', value)}
                        />

                        <Choice
                            id="level"
                            label={t('admin.announcements.level')}
                            value={form.data.level}
                            options={options.levels}
                            onChange={(value) => form.setData('level', value)}
                        />
                    </div>

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
                        <Label htmlFor="body">{t('admin.content.body')}</Label>
                        <textarea
                            id="body"
                            rows={5}
                            value={form.data.body}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                            required
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Choice
                            id="audience"
                            label={t('admin.announcements.audience')}
                            value={form.data.audience}
                            options={options.audiences}
                            onChange={(value) =>
                                form.setData('audience', value)
                            }
                        />

                        {form.data.audience === 'batch' && (
                            <Choice
                                id="batch_id"
                                label={t('admin.announcements.audience_batch')}
                                value={form.data.batch_id}
                                options={options.batches}
                                onChange={(value) =>
                                    form.setData('batch_id', value)
                                }
                            />
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="starts_at">
                                {t('admin.announcements.starts')}
                            </Label>
                            <Input
                                id="starts_at"
                                type="datetime-local"
                                value={form.data.starts_at}
                                onChange={(event) =>
                                    form.setData(
                                        'starts_at',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ends_at">
                                {t('admin.announcements.ends')}
                            </Label>
                            <Input
                                id="ends_at"
                                type="datetime-local"
                                value={form.data.ends_at}
                                onChange={(event) =>
                                    form.setData('ends_at', event.target.value)
                                }
                            />
                            <InputError message={form.errors.ends_at} />
                        </div>
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_pinned}
                            onCheckedChange={(checked) =>
                                form.setData('is_pinned', checked === true)
                            }
                        />
                        {t('admin.announcements.pinned')}
                    </label>

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

function Choice({
    id,
    label,
    value,
    options,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <div className="space-y-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger id={id}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
