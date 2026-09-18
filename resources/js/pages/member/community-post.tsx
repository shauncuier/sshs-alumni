import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Lock, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { MentionText } from '@/components/member/mention-text';
import {
    AuthorAvatar,
    AuthorName,
    PostCard,
} from '@/components/member/post-card';
import { ReactionBar } from '@/components/member/reaction-bar';
import { ReportDialog } from '@/components/member/report-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import MemberLayout from '@/layouts/member-layout';
import { formatDateTime } from '@/lib/format';
import type {
    CommunityComment,
    CommunityPost,
    MentionMap,
    Option,
} from '@/types/community';

type Props = {
    post: CommunityPost;
    comments: { data: CommunityComment[] };
    mentions: MentionMap;
    options: { reasons: Option[] };
    can: { comment: boolean };
};

export default function CommunityPostPage({
    post,
    comments,
    mentions,
    options,
    can,
}: Props) {
    const { t, choice } = useTranslation();
    const [replyTo, setReplyTo] = useState<CommunityComment | null>(null);

    return (
        <MemberLayout title={post.title ?? t('member.community.title')}>
            <div className="mx-auto max-w-3xl space-y-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/community">
                        <ArrowLeft
                            className="me-1 size-4 rtl:rotate-180"
                            aria-hidden="true"
                        />
                        {t('member.community.back')}
                    </Link>
                </Button>

                <PostCard
                    post={post}
                    mentions={mentions}
                    reasons={options.reasons}
                    linked={false}
                />

                <h2 className="pt-2 text-sm font-medium">
                    {choice('member.community.comments', post.comments_count)}
                </h2>

                <div className="space-y-3">
                    {comments.data.map((comment) => (
                        <CommentBlock
                            key={comment.id}
                            comment={comment}
                            mentions={mentions}
                            reasons={options.reasons}
                            onReply={setReplyTo}
                            canReply={can.comment}
                        />
                    ))}
                </div>

                {can.comment ? (
                    <CommentForm
                        postUlid={post.ulid}
                        replyTo={replyTo}
                        onCancelReply={() => setReplyTo(null)}
                    />
                ) : (
                    <p className="text-muted-foreground flex items-center gap-2 rounded-md border border-dashed px-4 py-3 text-sm">
                        <Lock className="size-4" aria-hidden="true" />
                        {t('member.community.comments_closed')}
                    </p>
                )}
            </div>
        </MemberLayout>
    );
}

function CommentBlock({
    comment,
    mentions,
    reasons,
    onReply,
    canReply,
    isReply = false,
}: {
    comment: CommunityComment;
    mentions: MentionMap;
    reasons: Option[];
    onReply: (comment: CommunityComment) => void;
    canReply: boolean;
    isReply?: boolean;
}) {
    const { t } = useTranslation();

    const remove = () => {
        if (!window.confirm(t('member.community.comment_delete_confirm'))) {
            return;
        }

        router.delete(`/community/comments/${comment.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardContent className="space-y-2 pt-5">
                <div className="flex items-start gap-3">
                    <AuthorAvatar author={comment.author} size="size-8" />

                    <div className="min-w-0 flex-1">
                        <AuthorName author={comment.author} />
                        <p className="text-muted-foreground text-xs">
                            {formatDateTime(comment.created_at)}
                        </p>
                    </div>
                </div>

                <MentionText body={comment.body} mentions={mentions} />

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <ReactionBar
                        url={`/community/comments/${comment.id}/reactions`}
                        mine={comment.my_reaction}
                        counts={comment.reaction_counts}
                        total={comment.reactions_count ?? 0}
                    />

                    <div className="flex items-center gap-1">
                        {/*
                         * Replies are one level deep. A reply to a reply
                         * attaches to the same parent — the server re-points
                         * it rather than rejecting it — so the button is only
                         * offered on top-level comments.
                         */}
                        {canReply && !isReply && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => onReply(comment)}
                            >
                                {t('member.community.reply')}
                            </Button>
                        )}

                        {comment.can.report && (
                            <ReportDialog
                                url={`/community/comments/${comment.id}/reports`}
                                reasons={reasons}
                            />
                        )}

                        {comment.can.delete && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive"
                                onClick={remove}
                            >
                                <Trash2
                                    className="me-1 size-3.5"
                                    aria-hidden="true"
                                />
                                {t('common.actions.delete')}
                            </Button>
                        )}
                    </div>
                </div>

                {comment.replies && comment.replies.length > 0 && (
                    <div className="space-y-3 border-s ps-4 pt-2">
                        {comment.replies.map((reply) => (
                            <CommentBlock
                                key={reply.id}
                                comment={reply}
                                mentions={mentions}
                                reasons={reasons}
                                onReply={onReply}
                                canReply={canReply}
                                isReply
                            />
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function CommentForm({
    postUlid,
    replyTo,
    onCancelReply,
}: {
    postUlid: string;
    replyTo: CommunityComment | null;
    onCancelReply: () => void;
}) {
    const { t } = useTranslation();

    const form = useForm<{ body: string; parent_id: number | null }>({
        body: '',
        parent_id: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            parent_id: replyTo?.id ?? null,
        }));

        form.post(`/community/${postUlid}/comments`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onCancelReply();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            {replyTo && (
                <p className="text-muted-foreground flex items-center gap-2 text-xs">
                    {t('member.community.replying_to', {
                        name: replyTo.author.name,
                    })}
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onCancelReply}
                    >
                        {t('common.actions.cancel')}
                    </Button>
                </p>
            )}

            <textarea
                rows={3}
                value={form.data.body}
                onChange={(event) => form.setData('body', event.target.value)}
                placeholder={t('member.community.comment_placeholder')}
                aria-label={t('member.community.comment_placeholder')}
                className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                required
            />
            <InputError message={form.errors.body} />

            <div className="flex justify-end">
                <Button type="submit" disabled={form.processing}>
                    {t('member.community.comment')}
                </Button>
            </div>
        </form>
    );
}
