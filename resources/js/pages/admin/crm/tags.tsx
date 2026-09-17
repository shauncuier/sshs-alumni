import { router, useForm } from '@inertiajs/react';
import { Plus, Tag as TagIcon, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { TagWithCounts } from '@/types/crm';

type Props = {
    tags: TagWithCounts[];
    can: { manage: boolean };
};

/**
 * Tags are shared vocabulary rather than personal bookmarks, which is why
 * creating one needs `crm.manage` and why the page says to keep the list short.
 */
export default function Tags({ tags, can }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.crm.tags')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.crm.tags')}
                    </h1>

                    {can.manage && <CreateTagForm />}
                </div>

                {tags.length === 0 ? (
                    <EmptyState
                        icon={TagIcon}
                        title={t('admin.crm.tags')}
                        description={t('admin.crm.tags_empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {tags.map((tag) => (
                            <div
                                key={tag.id}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <span
                                        className="inline-block rounded-full border px-3 py-1 text-sm font-medium"
                                        style={
                                            tag.color
                                                ? {
                                                      borderColor: tag.color,
                                                      color: tag.color,
                                                  }
                                                : undefined
                                        }
                                    >
                                        {tag.name}
                                    </span>

                                    {tag.description && (
                                        <p className="text-muted-foreground mt-1 truncate text-sm">
                                            {tag.description}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center gap-3">
                                    <span className="text-muted-foreground text-sm">
                                        {t('admin.crm.tag_used', {
                                            contacts: formatNumber(
                                                tag.contacts_count,
                                            ),
                                            members: formatNumber(
                                                tag.members_count,
                                            ),
                                        })}
                                    </span>

                                    {can.manage && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={t(
                                                'common.actions.delete',
                                            )}
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        t(
                                                            'admin.crm.tag_delete_confirm',
                                                        ),
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/admin/crm/tags/${tag.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            <Trash2
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function CreateTagForm() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: '',
        color: '#0e7a3c',
        description: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/crm/tags', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.crm.tag_add')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="tag_name">
                                {t('admin.crm.tag_name')}
                            </Label>
                            <Input
                                id="tag_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tag_color">
                                {t('admin.crm.tag_colour')}
                            </Label>
                            <Input
                                id="tag_color"
                                type="color"
                                value={form.data.color}
                                onChange={(e) =>
                                    form.setData('color', e.target.value)
                                }
                                className="h-9 p-1"
                            />
                            <InputError message={form.errors.color} />
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                        >
                            {t('common.actions.cancel')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
