import { Link, router, useForm } from '@inertiajs/react';
import { Eye, Newspaper, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { formatDate } from '@/lib/format';
import type { AdminNews, Option } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = {
    news: Paginated<AdminNews>;
    filters: { status: string | null; q: string | null };
    options: { statuses: Option[]; categories: string[] };
    can: { manage: boolean; publish: boolean };
};

export default function AdminNews({ news, filters, options, can }: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<AdminNews | null>(null);

    return (
        <AdminLayout title={t('admin.news.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.news.title')}
                    </h1>

                    {can.manage && <ArticleDialog options={options} />}
                </div>

                {!can.publish && can.manage && (
                    <p className="bg-muted text-muted-foreground rounded-md px-4 py-2 text-sm">
                        {t('admin.content.no_publish_permission')}
                    </p>
                )}

                <div className="flex flex-wrap gap-2">
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={(value) =>
                            router.get(
                                '/admin/news',
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
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {news.data.length === 0 ? (
                    <EmptyState
                        icon={Newspaper}
                        title={t('admin.news.title')}
                        description={t('admin.news.empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        {news.data.map((item) => (
                            <Card key={item.id}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {item.title}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {item.author}
                                                {item.published_at
                                                    ? ` · ${formatDate(item.published_at)}`
                                                    : ''}
                                                {item.category
                                                    ? ` · ${item.category}`
                                                    : ''}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-1">
                                            {item.is_featured && (
                                                <Badge className="bg-brand-gold-500 text-brand-green-900">
                                                    {t('admin.news.featured')}
                                                </Badge>
                                            )}
                                            <Badge
                                                variant={
                                                    item.status === 'published'
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {item.status_label}
                                            </Badge>
                                        </div>
                                    </div>

                                    <p className="text-muted-foreground text-sm">
                                        {item.excerpt ?? item.summary}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        {item.status === 'published' && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={item.url}>
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
                                                onClick={() => setEditing(item)}
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
                                                        `/admin/news/${item.id}/publish`,
                                                        {
                                                            publish:
                                                                item.status !==
                                                                'published',
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {item.status === 'published'
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
                                                        `/admin/news/${item.id}`,
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

                <Pagination meta={news.meta} />
            </div>

            {editing && (
                <ArticleDialog
                    options={options}
                    article={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </AdminLayout>
    );
}

function ArticleDialog({
    options,
    article,
    onClose,
}: {
    options: { categories: string[] };
    article?: AdminNews;
    onClose?: () => void;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(article !== undefined);

    const form = useForm({
        title: article?.title ?? '',
        excerpt: article?.excerpt ?? '',
        body: article?.body ?? '',
        category: article?.category ?? '',
        is_featured: article?.is_featured ?? false,
        meta_description: article?.meta_description ?? '',
    });

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options_ = {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                close();
            },
        };

        if (article) {
            form.put(`/admin/news/${article.id}`, options_);
        } else {
            form.post('/admin/news', options_);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? setOpen(true) : close())}
        >
            {!article && (
                <DialogTrigger asChild>
                    <Button size="sm">
                        <Plus className="me-1 size-4" aria-hidden="true" />
                        {t('admin.news.create')}
                    </Button>
                </DialogTrigger>
            )}

            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.news.create')}</DialogTitle>
                        <DialogDescription>
                            {t('admin.content.draft_note')}
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
                        <Label htmlFor="excerpt">
                            {t('admin.news.excerpt')}
                        </Label>
                        <textarea
                            id="excerpt"
                            rows={2}
                            value={form.data.excerpt}
                            onChange={(event) =>
                                form.setData('excerpt', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <p className="text-muted-foreground text-xs">
                            {t('admin.news.excerpt_hint')}
                        </p>
                        <InputError message={form.errors.excerpt} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="body">{t('admin.content.body')}</Label>
                        <textarea
                            id="body"
                            rows={10}
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
                        <div className="space-y-1.5">
                            <Label htmlFor="category">
                                {t('admin.news.category')}
                            </Label>
                            <Input
                                id="category"
                                list="news-categories"
                                value={form.data.category}
                                onChange={(event) =>
                                    form.setData('category', event.target.value)
                                }
                            />
                            {/* Suggests names already in use, so the filter
                                never offers a synonym of an existing one. */}
                            <datalist id="news-categories">
                                {options.categories.map((category) => (
                                    <option key={category} value={category} />
                                ))}
                            </datalist>
                            <InputError message={form.errors.category} />
                        </div>

                        <div className="flex items-end">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.is_featured}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_featured',
                                            checked === true,
                                        )
                                    }
                                />
                                {t('admin.news.featured')}
                            </label>
                        </div>
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
                        <p className="text-muted-foreground text-xs">
                            {t('admin.content.seo_hint')}
                        </p>
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
