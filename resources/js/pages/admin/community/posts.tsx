import { Link, router, useForm } from '@inertiajs/react';
import {
    EyeOff,
    Flag,
    MessageSquareOff,
    MessagesSquare,
    Pin,
    RotateCcw,
    Search,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
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
import { formatDateTime, formatNumber } from '@/lib/format';
import type { ModeratedPost, Option } from '@/types/community';
import type { Paginated } from '@/types/member';

type Props = {
    posts: Paginated<ModeratedPost>;
    filters: {
        status: string | null;
        category: string | null;
        q: string | null;
    };
    options: { statuses: Option[]; categories: Option[] };
    open_reports: number;
};

export default function AdminCommunityPosts({
    posts,
    filters,
    options,
    open_reports: openReports,
}: Props) {
    const { t, choice } = useTranslation();
    const [search, setSearch] = useState(filters.q ?? '');
    const [acting, setActing] = useState<{
        post: ModeratedPost;
        status: string;
    } | null>(null);

    const go = (next: Record<string, string | null>) => {
        router.get(
            '/admin/community/posts',
            {
                status: filters.status ?? undefined,
                category: filters.category ?? undefined,
                q: filters.q ?? undefined,
                ...next,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const quick = (
        post: ModeratedPost,
        payload: Record<string, string | boolean>,
    ) => {
        router.put(`/admin/community/posts/${post.ulid}`, payload, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={t('admin.community.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.community.posts')}
                    </h1>

                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/community/reports">
                            <Flag className="me-1 size-4" aria-hidden="true" />
                            {choice(
                                'admin.community.open_reports',
                                openReports,
                            )}
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            go({ q: search === '' ? null : search });
                        }}
                        className="flex gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t(
                                'admin.community.search_placeholder',
                            )}
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
                        value={filters.status ?? 'all'}
                        onValueChange={(value) =>
                            go({ status: value === 'all' ? null : value })
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

                    <Select
                        value={filters.category ?? 'all'}
                        onValueChange={(value) =>
                            go({ category: value === 'all' ? null : value })
                        }
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('admin.community.filter_category')}
                            </SelectItem>
                            {options.categories.map((category) => (
                                <SelectItem
                                    key={category.value}
                                    value={category.value}
                                >
                                    {category.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {posts.data.length === 0 ? (
                    <EmptyState
                        icon={MessagesSquare}
                        title={t('admin.community.posts')}
                        description={t('admin.community.empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        {posts.data.map((post) => (
                            <Card key={post.ulid}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {post.title ?? post.excerpt}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {post.author}
                                                {' · '}
                                                {formatDateTime(
                                                    post.created_at,
                                                )}
                                                {' · '}
                                                {t('admin.community.counts', {
                                                    comments: formatNumber(
                                                        post.comments_count,
                                                    ),
                                                    reactions: formatNumber(
                                                        post.reactions_count,
                                                    ),
                                                })}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center justify-end gap-1">
                                            {post.open_reports_count > 0 && (
                                                <Badge variant="destructive">
                                                    {formatNumber(
                                                        post.open_reports_count,
                                                    )}
                                                </Badge>
                                            )}
                                            {post.is_pinned && (
                                                <Badge variant="secondary">
                                                    {t(
                                                        'admin.community.pinned',
                                                    )}
                                                </Badge>
                                            )}
                                            {!post.comments_enabled && (
                                                <Badge variant="outline">
                                                    {t(
                                                        'admin.community.closed',
                                                    )}
                                                </Badge>
                                            )}
                                            {post.deleted && (
                                                <Badge variant="outline">
                                                    {t(
                                                        'admin.community.deleted',
                                                    )}
                                                </Badge>
                                            )}
                                            <Badge
                                                variant={
                                                    post.status === 'published'
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {post.status_label}
                                            </Badge>
                                            <Badge variant="outline">
                                                {post.category_label}
                                            </Badge>
                                        </div>
                                    </div>

                                    <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                                        {post.excerpt}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={post.url}>
                                                {t('admin.community.view_item')}
                                            </Link>
                                        </Button>

                                        {post.status === 'published' ? (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setActing({
                                                            post,
                                                            status: 'hidden',
                                                        })
                                                    }
                                                >
                                                    <EyeOff
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.community.hide')}
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive"
                                                    onClick={() =>
                                                        setActing({
                                                            post,
                                                            status: 'removed',
                                                        })
                                                    }
                                                >
                                                    <Trash2
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t(
                                                        'admin.community.remove',
                                                    )}
                                                </Button>
                                            </>
                                        ) : (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    quick(post, {
                                                        status: 'published',
                                                    })
                                                }
                                            >
                                                <RotateCcw
                                                    className="me-1 size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t('admin.community.restore')}
                                            </Button>
                                        )}

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                quick(post, {
                                                    is_pinned: !post.is_pinned,
                                                })
                                            }
                                        >
                                            <Pin
                                                className="me-1 size-3.5"
                                                aria-hidden="true"
                                            />
                                            {post.is_pinned
                                                ? t('admin.community.unpin')
                                                : t('admin.community.pin')}
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                quick(post, {
                                                    comments_enabled:
                                                        !post.comments_enabled,
                                                })
                                            }
                                        >
                                            <MessageSquareOff
                                                className="me-1 size-3.5"
                                                aria-hidden="true"
                                            />
                                            {post.comments_enabled
                                                ? t(
                                                      'admin.community.close_comments',
                                                  )
                                                : t(
                                                      'admin.community.open_comments',
                                                  )}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={posts.meta} />
            </div>

            {acting && (
                <ReasonDialog
                    post={acting.post}
                    status={acting.status}
                    onClose={() => setActing(null)}
                />
            )}
        </AdminLayout>
    );
}

/**
 * Hiding or removing asks for a reason first.
 *
 * The reason goes on the audit log under the moderator's name. Asking for it
 * at the moment of the decision is the only time it can be captured honestly —
 * a month later nobody remembers.
 */
function ReasonDialog({
    post,
    status,
    onClose,
}: {
    post: ModeratedPost;
    status: string;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm({ status, reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.put(`/admin/community/posts/${post.ulid}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={onClose}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {status === 'hidden'
                                ? t('admin.community.hide')
                                : t('admin.community.remove')}
                        </DialogTitle>
                        <DialogDescription>
                            {status === 'hidden'
                                ? t('admin.community.hide_help')
                                : t('admin.community.remove_help')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="reason">
                            {t('admin.community.reason_label')}
                        </Label>
                        <textarea
                            id="reason"
                            rows={3}
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <p className="text-muted-foreground text-xs">
                            {t('admin.community.reason_help')}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.confirm')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
