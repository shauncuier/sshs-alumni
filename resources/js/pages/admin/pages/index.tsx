import { Link, router, useForm } from '@inertiajs/react';
import { Eye, FileText, Lock, Pencil, Plus, Trash2 } from 'lucide-react';
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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminPage } from '@/types/content';

type Props = {
    pages: AdminPage[];
    can: { manage: boolean; publish: boolean };
};

/**
 * Standing pages.
 *
 * The privacy policy and the terms carry a padlock and no delete button — and
 * the endpoint refuses them too, so the padlock is a statement of the rule
 * rather than the whole of it.
 */
export default function AdminPages({ pages, can }: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<AdminPage | null>(null);

    return (
        <AdminLayout title={t('admin.pages.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.pages.title')}
                    </h1>

                    {can.manage && <PageDialog />}
                </div>

                {pages.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title={t('admin.pages.title')}
                        description={t('admin.pages.empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        {pages.map((page) => (
                            <Card key={page.id}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {page.title}
                                            </p>
                                            <p className="text-muted-foreground font-mono text-xs">
                                                /p/{page.slug}
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            {page.is_system && (
                                                <Badge
                                                    variant="secondary"
                                                    className="gap-1"
                                                >
                                                    <Lock
                                                        className="size-3"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.pages.system')}
                                                </Badge>
                                            )}
                                            <Badge
                                                variant={
                                                    page.status === 'published'
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {page.status_label}
                                            </Badge>
                                        </div>
                                    </div>

                                    {page.is_system && (
                                        <p className="text-muted-foreground text-xs">
                                            {t('admin.pages.system_hint')}
                                        </p>
                                    )}

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        {page.status === 'published' && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={page.url}>
                                                    <Eye
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.content.preview')}
                                                </Link>
                                            </Button>
                                        )}

                                        {can.manage && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => setEditing(page)}
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
                                                        `/admin/pages/${page.id}/publish`,
                                                        {
                                                            publish:
                                                                page.status !==
                                                                'published',
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {page.status === 'published'
                                                    ? t(
                                                          'admin.content.unpublish',
                                                      )
                                                    : t(
                                                          'admin.content.publish',
                                                      )}
                                            </Button>
                                        )}

                                        {can.manage && !page.is_system && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    router.delete(
                                                        `/admin/pages/${page.id}`,
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
            </div>

            {editing && (
                <PageDialog page={editing} onClose={() => setEditing(null)} />
            )}
        </AdminLayout>
    );
}

function PageDialog({
    page,
    onClose,
}: {
    page?: AdminPage;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(page !== undefined);

    const form = useForm({
        title: page?.title ?? '',
        body: page?.body ?? '',
        meta_title: page?.meta_title ?? '',
        meta_description: page?.meta_description ?? '',
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

        if (page) {
            form.put(`/admin/pages/${page.id}`, opts);
        } else {
            form.post('/admin/pages', opts);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!page && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.pages.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {page?.title ?? t('admin.pages.create')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('admin.pages.slug_fixed')}
                        </DialogDescription>
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
                        <Label htmlFor="body">{t('admin.content.body')}</Label>
                        <textarea
                            id="body"
                            rows={14}
                            value={form.data.body ?? ''}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 font-mono text-sm focus-visible:ring-1 focus-visible:outline-none"
                            required
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="meta_description">
                            {t('admin.content.meta_description')}
                        </Label>
                        <Input
                            id="meta_description"
                            value={form.data.meta_description}
                            onChange={(event) =>
                                form.setData(
                                    'meta_description',
                                    event.target.value,
                                )
                            }
                        />
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
