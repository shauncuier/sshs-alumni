import { router, useForm } from '@inertiajs/react';
import { Pencil, Plus, ScrollText, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
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
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import type { Milestone } from '@/types/content';

type Options = { earliest_year: number; latest_year: number };

type Props = {
    milestones: Milestone[];
    options: Options;
    can: { manage: boolean };
};

export default function AdminHistory({ milestones, options, can }: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<Milestone | null>(null);

    return (
        <AdminLayout title={t('admin.history.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t('admin.history.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 max-w-prose text-sm">
                            {t('admin.history.intro')}
                        </p>
                    </div>

                    {can.manage && <MilestoneDialog options={options} />}
                </div>

                {milestones.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title={t('admin.history.title')}
                        description={t('admin.history.empty')}
                    />
                ) : (
                    <ol className="space-y-3">
                        {milestones.map((milestone) => (
                            <Card key={milestone.id}>
                                <CardContent className="flex flex-wrap items-start justify-between gap-3 pt-6">
                                    <div className="flex gap-4">
                                        <p className="text-brand-green-900 w-16 shrink-0 text-lg font-semibold tabular-nums">
                                            {milestone.year}
                                        </p>

                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {milestone.title}
                                                {milestone.is_highlighted && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="ms-2"
                                                    >
                                                        {t(
                                                            'admin.history.highlighted',
                                                        )}
                                                    </Badge>
                                                )}
                                            </p>

                                            {milestone.description && (
                                                <p className="text-muted-foreground mt-1 text-sm">
                                                    {milestone.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {can.manage && (
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditing(milestone)
                                                }
                                            >
                                                <Pencil
                                                    className="me-1 size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t('common.actions.edit')}
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    router.delete(
                                                        `/admin/history/${milestone.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                <span className="sr-only">
                                                    {t('common.actions.delete')}
                                                </span>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </ol>
                )}
            </div>

            {editing && (
                <MilestoneDialog
                    options={options}
                    milestone={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminLayout>
    );
}

function MilestoneDialog({
    options,
    milestone,
    onClose,
}: {
    options: Options;
    milestone?: Milestone;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(milestone !== undefined);

    const form = useForm({
        year: milestone?.year ?? options.latest_year - 1,
        date_label: milestone?.date_label ?? '',
        title: milestone?.title ?? '',
        description: milestone?.description ?? '',
        is_highlighted: milestone?.is_highlighted ?? false,
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

        if (milestone) {
            form.put(`/admin/history/${milestone.id}`, opts);
        } else {
            form.post('/admin/history', opts);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!milestone && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.history.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.history.create')}</DialogTitle>
                        <DialogDescription>
                            {t('admin.history.intro')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="year">
                                {t('admin.history.year')}
                            </Label>
                            <Input
                                id="year"
                                type="number"
                                inputMode="numeric"
                                min={options.earliest_year}
                                max={options.latest_year}
                                value={form.data.year}
                                onChange={(event) =>
                                    form.setData(
                                        'year',
                                        Number(event.target.value),
                                    )
                                }
                                required
                            />
                            <InputError message={form.errors.year} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="date_label">
                                {t('admin.history.date_label')}
                            </Label>
                            <Input
                                id="date_label"
                                value={form.data.date_label}
                                onChange={(event) =>
                                    form.setData(
                                        'date_label',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
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
                        <Label htmlFor="description">
                            {t('common.labels.description')}
                        </Label>
                        <textarea
                            id="description"
                            rows={4}
                            value={form.data.description ?? ''}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                        />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_highlighted}
                            onCheckedChange={(checked) =>
                                form.setData('is_highlighted', checked === true)
                            }
                        />
                        {t('admin.history.highlighted')}
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
