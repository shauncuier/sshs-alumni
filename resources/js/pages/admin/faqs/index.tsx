import { router, useForm } from '@inertiajs/react';
import { CircleHelp, Pencil, Plus, Trash2 } from 'lucide-react';
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
import type { AdminFaq, Option } from '@/types/content';

type Props = {
    faqs: AdminFaq[];
    options: { groups: Option[] };
    can: { manage: boolean };
};

export default function AdminFaqs({ faqs, options, can }: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<AdminFaq | null>(null);

    const groups = options.groups.filter((group) =>
        faqs.some((faq) => faq.group === group.value),
    );

    return (
        <AdminLayout title={t('admin.faqs.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.faqs.title')}
                    </h1>

                    {can.manage && <FaqDialog options={options} />}
                </div>

                {faqs.length === 0 ? (
                    <EmptyState
                        icon={CircleHelp}
                        title={t('admin.faqs.title')}
                        description={t('admin.faqs.empty')}
                    />
                ) : (
                    groups.map((group) => (
                        <section key={group.value} className="space-y-3">
                            <h2 className="text-muted-foreground text-sm font-medium">
                                {group.label}
                            </h2>

                            {faqs
                                .filter((faq) => faq.group === group.value)
                                .map((faq) => (
                                    <Card key={faq.id}>
                                        <CardContent className="space-y-2 pt-6">
                                            <div className="flex flex-wrap items-start justify-between gap-2">
                                                <p className="font-medium">
                                                    {faq.question}
                                                </p>

                                                {!faq.is_published && (
                                                    <Badge variant="secondary">
                                                        {t(
                                                            'enums.content_status.draft',
                                                        )}
                                                    </Badge>
                                                )}
                                            </div>

                                            <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                                                {faq.answer}
                                            </p>

                                            {can.manage && (
                                                <div className="flex items-center gap-1 border-t pt-3">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setEditing(faq)
                                                        }
                                                    >
                                                        <Pencil
                                                            className="me-1 size-3.5"
                                                            aria-hidden="true"
                                                        />
                                                        {t(
                                                            'common.actions.edit',
                                                        )}
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/admin/faqs/${faq.id}`,
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
                                                            {t(
                                                                'common.actions.delete',
                                                            )}
                                                        </span>
                                                    </Button>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))}
                        </section>
                    ))
                )}
            </div>

            {editing && (
                <FaqDialog
                    options={options}
                    faq={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminLayout>
    );
}

function FaqDialog({
    options,
    faq,
    onClose,
}: {
    options: { groups: Option[] };
    faq?: AdminFaq;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(faq !== undefined);

    const form = useForm({
        group: faq?.group ?? options.groups[0]?.value ?? 'general',
        question: faq?.question ?? '',
        answer: faq?.answer ?? '',
        display_order: faq?.display_order ?? 0,
        is_published: faq?.is_published ?? true,
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

        if (faq) {
            form.put(`/admin/faqs/${faq.id}`, opts);
        } else {
            form.post('/admin/faqs', opts);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!faq && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.faqs.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.faqs.create')}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="group">{t('admin.faqs.group')}</Label>
                        <Select
                            value={form.data.group}
                            onValueChange={(value) =>
                                form.setData('group', value)
                            }
                        >
                            <SelectTrigger id="group">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.groups.map((group) => (
                                    <SelectItem
                                        key={group.value}
                                        value={group.value}
                                    >
                                        {group.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="question">
                            {t('admin.faqs.question')}
                        </Label>
                        <Input
                            id="question"
                            value={form.data.question}
                            onChange={(event) =>
                                form.setData('question', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.question} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="answer">{t('admin.faqs.answer')}</Label>
                        <textarea
                            id="answer"
                            rows={5}
                            value={form.data.answer}
                            onChange={(event) =>
                                form.setData('answer', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                            required
                        />
                        <InputError message={form.errors.answer} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.is_published}
                            onCheckedChange={(checked) =>
                                form.setData('is_published', checked === true)
                            }
                        />
                        {t('admin.faqs.published')}
                    </label>

                    <p className="text-muted-foreground text-xs">
                        {t('admin.faqs.unpublished_hint')}
                    </p>

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
