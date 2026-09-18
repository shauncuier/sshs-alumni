import { Link, router } from '@inertiajs/react';
import { BookOpen, Check, Star, X } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { AdminStory, Option } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = {
    stories: Paginated<AdminStory>;
    filters: { status: string };
    options: { statuses: Option[] };
    can: { manage: boolean; publish: boolean };
};

/**
 * The story review queue.
 *
 * The whole story is shown, not an excerpt. A reviewer deciding whether
 * somebody's account of their own life goes on the association's website
 * should not be doing it from the first two lines.
 */
export default function AdminStories({
    stories,
    filters,
    options,
    can,
}: Props) {
    const { t } = useTranslation();

    const review = (story: AdminStory, status: string) => {
        router.put(
            `/admin/stories/${story.id}/review`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <AdminLayout title={t('admin.stories.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t('admin.stories.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('admin.stories.read_first')}
                        </p>
                    </div>

                    <Select
                        value={filters.status}
                        onValueChange={(value) =>
                            router.get(
                                '/admin/stories',
                                { status: value },
                                { preserveState: true, replace: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('admin.community.filter_all')}
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

                {stories.data.length === 0 ? (
                    <EmptyState
                        icon={BookOpen}
                        title={t('admin.stories.title')}
                        description={
                            filters.status === 'pending'
                                ? t('admin.stories.queue_empty')
                                : t('admin.stories.empty')
                        }
                    />
                ) : (
                    <div className="space-y-4">
                        {stories.data.map((story) => (
                            <Card key={story.id}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            {story.photo_url && (
                                                <img
                                                    src={story.photo_url}
                                                    alt=""
                                                    className="size-12 rounded-full object-cover"
                                                />
                                            )}

                                            <div>
                                                <p className="font-medium">
                                                    {story.title}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {t('admin.stories.by', {
                                                        name: story.author_name,
                                                    })}
                                                    {story.batch
                                                        ? ` · ${story.batch}`
                                                        : ''}
                                                    {story.submitted_at
                                                        ? ` · ${t('admin.stories.submitted', { date: formatDate(story.submitted_at) })}`
                                                        : ''}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            {story.is_featured && (
                                                <Badge className="bg-brand-gold-500 text-brand-green-900">
                                                    {t(
                                                        'admin.stories.featured',
                                                    )}
                                                </Badge>
                                            )}
                                            <Badge
                                                variant={
                                                    story.status === 'published'
                                                        ? 'outline'
                                                        : 'secondary'
                                                }
                                            >
                                                {story.status_label}
                                            </Badge>
                                        </div>
                                    </div>

                                    {story.career_summary && (
                                        <p className="text-muted-foreground text-sm">
                                            {story.career_summary}
                                        </p>
                                    )}

                                    {/* The whole story, not an excerpt. */}
                                    <div className="bg-muted/50 max-h-72 overflow-y-auto rounded-md p-4 text-sm leading-relaxed whitespace-pre-wrap">
                                        {story.body}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        {story.url && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={story.url}>
                                                    {t('admin.content.preview')}
                                                </Link>
                                            </Button>
                                        )}

                                        {can.publish &&
                                            story.status !== 'published' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        review(
                                                            story,
                                                            'published',
                                                        )
                                                    }
                                                >
                                                    <Check
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.stories.publish')}
                                                </Button>
                                            )}

                                        {can.publish &&
                                            story.status !== 'pending' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        review(story, 'pending')
                                                    }
                                                >
                                                    {t('admin.stories.hold')}
                                                </Button>
                                            )}

                                        {can.publish &&
                                            story.status !== 'rejected' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive"
                                                    onClick={() =>
                                                        review(
                                                            story,
                                                            'rejected',
                                                        )
                                                    }
                                                >
                                                    <X
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.stories.reject')}
                                                </Button>
                                            )}

                                        {can.manage &&
                                            story.status === 'published' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.put(
                                                            `/admin/stories/${story.id}`,
                                                            {
                                                                is_featured:
                                                                    !story.is_featured,
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Star
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t(
                                                        'admin.stories.featured',
                                                    )}
                                                </Button>
                                            )}
                                    </div>

                                    {story.status === 'rejected' && (
                                        <p className="text-muted-foreground text-xs">
                                            {t('admin.stories.reject_note')}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={stories.meta} />
            </div>
        </AdminLayout>
    );
}
