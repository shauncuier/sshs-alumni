import { router, useForm } from '@inertiajs/react';
import { MessagesSquare, Search, Send, X } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PostCard } from '@/components/member/post-card';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import MemberLayout from '@/layouts/member-layout';
import { cn } from '@/lib/utils';
import type { CommunityPost, MentionMap, Option } from '@/types/community';
import type { Paginated } from '@/types/member';

type Props = {
    posts: Paginated<CommunityPost>;
    mentions: MentionMap;
    filters: { category: string | null; q: string | null; mine: boolean };
    options: { categories: Option[]; reasons: Option[]; max_photos: number };
    viewer: { batch: string | null };
};

export default function Community({
    posts,
    mentions,
    filters,
    options,
    viewer,
}: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.q ?? '');

    const go = (next: Record<string, string | boolean | null>) => {
        router.get(
            '/community',
            {
                category: filters.category ?? undefined,
                q: filters.q ?? undefined,
                mine: filters.mine || undefined,
                ...next,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        go({ q: search === '' ? null : search });
    };

    return (
        <MemberLayout title={t('member.community.title')}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {t('member.community.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('member.community.intro')}
                    </p>
                </div>

                <Composer options={options} viewerBatch={viewer.batch} />

                <div className="flex flex-wrap items-center gap-2">
                    <form onSubmit={submitSearch} className="flex gap-2">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t(
                                'member.community.search_placeholder',
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
                                {t('member.community.all_posts')}
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

                    <Button
                        type="button"
                        variant={filters.mine ? 'default' : 'outline'}
                        size="sm"
                        aria-pressed={filters.mine}
                        onClick={() => go({ mine: !filters.mine })}
                    >
                        {t('member.community.mine')}
                    </Button>

                    {(filters.category || filters.q || filters.mine) && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setSearch('');
                                router.get('/community');
                            }}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {posts.data.length === 0 ? (
                    <EmptyState
                        icon={MessagesSquare}
                        title={t('member.community.title')}
                        description={
                            filters.category || filters.q || filters.mine
                                ? t('member.community.empty_filtered')
                                : t('member.community.empty')
                        }
                    />
                ) : (
                    <div className="space-y-4">
                        {posts.data.map((post) => (
                            <PostCard
                                key={post.ulid}
                                post={post}
                                mentions={mentions}
                                reasons={options.reasons}
                            />
                        ))}
                    </div>
                )}

                <Pagination meta={posts.meta} />
            </div>
        </MemberLayout>
    );
}

/**
 * The composer.
 *
 * Collapsed to a single line until it is clicked: the feed is what members
 * came for, and a four-field form at the top of it pushes the first post below
 * the fold on a phone.
 */
function Composer({
    options,
    viewerBatch,
}: {
    options: { categories: Option[]; max_photos: number };
    viewerBatch: string | null;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm<{
        category: string;
        title: string;
        body: string;
        photos: File[];
    }>({
        category: options.categories[0]?.value ?? 'general',
        title: '',
        body: '',
        photos: [],
    });

    const isBatchPost = form.data.category === 'batch';

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/community', {
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    const pickPhotos = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData(
            'photos',
            Array.from(event.target.files ?? []).slice(0, options.max_photos),
        );
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="border-input bg-background text-muted-foreground hover:bg-accent w-full rounded-lg border px-4 py-3 text-start text-sm transition-colors"
            >
                {t('member.community.compose_placeholder')}
            </button>
        );
    }

    return (
        <Card>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="category">
                                {t('member.community.category')}
                            </Label>
                            <Select
                                value={form.data.category}
                                onValueChange={(value) =>
                                    form.setData('category', value)
                                }
                            >
                                <SelectTrigger id="category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
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
                            <InputError message={form.errors.category} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="title">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="title"
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                                placeholder={t(
                                    'member.community.title_placeholder',
                                )}
                            />
                            <InputError message={form.errors.title} />
                        </div>
                    </div>

                    {/*
                     * Batch posts go to the author's own batch and nowhere
                     * else — there is no field for choosing one. A member with
                     * no batch yet is told what to do about it rather than
                     * being allowed to post into nothing.
                     */}
                    {isBatchPost && (
                        <p
                            className={cn(
                                'rounded-md px-3 py-2 text-xs',
                                viewerBatch
                                    ? 'bg-muted text-muted-foreground'
                                    : 'bg-destructive/10 text-destructive',
                            )}
                        >
                            {viewerBatch
                                ? t('member.community.batch_note', {
                                      batch: viewerBatch,
                                  })
                                : t('member.community.batch_missing')}
                        </p>
                    )}

                    <div className="space-y-1.5">
                        <Label htmlFor="body">
                            {t('member.community.compose')}
                        </Label>
                        <textarea
                            id="body"
                            rows={4}
                            value={form.data.body}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            placeholder={t(
                                'member.community.compose_placeholder',
                            )}
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                            required
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="photos">
                            {t('member.community.photos')}
                        </Label>
                        <Input
                            id="photos"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            onChange={pickPhotos}
                        />
                        <p className="text-muted-foreground text-xs">
                            {t('member.community.photos_help', {
                                max: options.max_photos,
                            })}
                        </p>
                        <InputError message={form.errors.photos} />
                    </div>

                    <div className="flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                form.reset();
                                setOpen(false);
                            }}
                        >
                            {t('common.actions.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing || (isBatchPost && !viewerBatch)
                            }
                        >
                            <Send className="me-1 size-4" aria-hidden="true" />
                            {t('member.community.post')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
