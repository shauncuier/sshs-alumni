import { Link, router, useForm } from '@inertiajs/react';
import { BookOpen, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import MemberLayout from '@/layouts/member-layout';
import { formatDate } from '@/lib/format';
import type { MemberStory } from '@/types/content';

type Props = { stories: MemberStory[] };

/**
 * A member's own story, whatever state it is in.
 *
 * A rejected story is shown, and says so. A submission that silently never
 * appears is how people conclude they were ignored.
 */
export default function MemberStories({ stories }: Props) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState<MemberStory | null>(null);

    return (
        <MemberLayout title={t('member.stories.title')}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {t('member.stories.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('member.stories.intro')}
                    </p>
                </div>

                {stories.length === 0 ? (
                    <>
                        <EmptyState
                            icon={BookOpen}
                            title={t('member.stories.title')}
                            description={t('member.stories.empty')}
                        />
                        <StoryForm />
                    </>
                ) : (
                    <div className="space-y-4">
                        {stories.map((story) => (
                            <Card key={story.id}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {story.title}
                                            </p>
                                            {story.career_summary && (
                                                <p className="text-muted-foreground text-xs">
                                                    {story.career_summary}
                                                </p>
                                            )}
                                        </div>

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

                                    <p className="text-muted-foreground text-sm leading-relaxed whitespace-pre-wrap">
                                        {story.body}
                                    </p>

                                    <p className="text-muted-foreground text-xs">
                                        {story.status === 'pending' &&
                                            t('member.stories.pending_note')}
                                        {story.status === 'published' &&
                                            t('member.stories.published_note', {
                                                date: formatDate(
                                                    story.published_at,
                                                ),
                                            })}
                                        {story.status === 'rejected' &&
                                            t('member.stories.rejected_note')}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                                        {story.url && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={story.url}>
                                                    {t('member.stories.view')}
                                                </Link>
                                            </Button>
                                        )}

                                        {story.editable && (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setEditing(story)
                                                    }
                                                >
                                                    {t('common.actions.edit')}
                                                </Button>

                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive"
                                                    onClick={() => {
                                                        if (
                                                            window.confirm(
                                                                t(
                                                                    'member.stories.withdraw_confirm',
                                                                ),
                                                            )
                                                        ) {
                                                            router.delete(
                                                                `/my/stories/${story.id}`,
                                                            );
                                                        }
                                                    }}
                                                >
                                                    <Trash2
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t(
                                                        'member.stories.withdraw',
                                                    )}
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {editing && (
                    <StoryForm
                        story={editing}
                        onDone={() => setEditing(null)}
                    />
                )}
            </div>
        </MemberLayout>
    );
}

function StoryForm({
    story,
    onDone,
}: {
    story?: MemberStory;
    onDone?: () => void;
}) {
    const { t } = useTranslation();

    const form = useForm<{
        title: string;
        body: string;
        career_summary: string;
        photo: File | null;
    }>({
        title: story?.title ?? '',
        body: story?.body ?? '',
        career_summary: story?.career_summary ?? '',
        photo: null,
    });

    const pickPhoto = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData('photo', event.target.files?.[0] ?? null);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const opts = {
            forceFormData: true,
            onSuccess: () => {
                form.reset();
                onDone?.();
            },
        };

        if (story) {
            form.put(`/my/stories/${story.id}`, opts);
        } else {
            form.post('/my/stories', opts);
        }
    };

    return (
        <Card>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="title">
                            {t('member.stories.story_title')}
                        </Label>
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
                        <Label htmlFor="career_summary">
                            {t('member.stories.career')}
                        </Label>
                        <Input
                            id="career_summary"
                            value={form.data.career_summary}
                            onChange={(event) =>
                                form.setData(
                                    'career_summary',
                                    event.target.value,
                                )
                            }
                        />
                        <p className="text-muted-foreground text-xs">
                            {t('member.stories.career_hint')}
                        </p>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="body">{t('member.stories.body')}</Label>
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
                        <p className="text-muted-foreground text-xs">
                            {t('member.stories.body_hint')}
                        </p>
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="photo">
                            {t('member.stories.photo')}
                        </Label>
                        <Input
                            id="photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={pickPhoto}
                        />
                        <InputError message={form.errors.photo} />
                    </div>

                    <div className="flex items-center justify-end gap-2">
                        {onDone && (
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={onDone}
                            >
                                {t('common.actions.cancel')}
                            </Button>
                        )}

                        <Button type="submit" disabled={form.processing}>
                            <Send className="me-1 size-4" aria-hidden="true" />
                            {t('member.stories.submit')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
