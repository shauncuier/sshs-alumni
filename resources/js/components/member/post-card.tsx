import { Link, router } from '@inertiajs/react';
import { EyeOff, MessageSquare, Pin, Trash2 } from 'lucide-react';
import { MentionText } from '@/components/member/mention-text';
import { ReactionBar } from '@/components/member/reaction-bar';
import { ReportDialog } from '@/components/member/report-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format';
import type {
    CommunityAuthor,
    CommunityPost,
    MentionMap,
    Option,
} from '@/types/community';

type Props = {
    post: CommunityPost;
    mentions: MentionMap;
    reasons: Option[];
    /** The feed links through to the post; the post page does not link to itself. */
    linked?: boolean;
};

export function PostCard({ post, mentions, reasons, linked = true }: Props) {
    const { t, choice } = useTranslation();

    const remove = () => {
        if (!window.confirm(t('member.community.delete_confirm'))) {
            return;
        }

        router.delete(`/community/${post.ulid}`);
    };

    return (
        <Card>
            <CardContent className="space-y-3 pt-6">
                <div className="flex items-start gap-3">
                    <AuthorAvatar author={post.author} />

                    <div className="min-w-0 flex-1">
                        <AuthorName author={post.author} />

                        <p className="text-muted-foreground text-xs">
                            {formatDateTime(post.created_at)}
                            {post.author.batch ? ` · ${post.author.batch}` : ''}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center justify-end gap-1">
                        {post.is_pinned && (
                            <Badge variant="secondary" className="gap-1">
                                <Pin className="size-3" aria-hidden="true" />
                                {t('member.community.pinned')}
                            </Badge>
                        )}
                        <Badge variant="outline">{post.category_label}</Badge>
                        {post.batch && (
                            <Badge variant="outline">{post.batch}</Badge>
                        )}
                    </div>
                </div>

                {/*
                 * The author's own view of a post a moderator has taken out of
                 * the feed. Saying so is the point: a post that silently stops
                 * appearing to everyone but you is how people conclude the
                 * committee is deleting things behind their backs.
                 */}
                {post.status !== 'published' && (
                    <p className="bg-muted text-muted-foreground flex items-center gap-2 rounded-md px-3 py-2 text-xs">
                        <EyeOff className="size-3.5" aria-hidden="true" />
                        {post.status === 'hidden'
                            ? t('member.community.hidden_notice')
                            : t('member.community.removed_notice')}
                    </p>
                )}

                {post.title &&
                    (linked ? (
                        <Link
                            href={`/community/${post.ulid}`}
                            className="block font-semibold hover:underline"
                        >
                            {post.title}
                        </Link>
                    ) : (
                        <h1 className="text-lg font-semibold">{post.title}</h1>
                    ))}

                <MentionText body={post.body} mentions={mentions} />

                {post.photos && post.photos.length > 0 && (
                    <div className="grid grid-cols-2 gap-2">
                        {post.photos.map((photo) => (
                            <a
                                key={photo.url}
                                href={photo.url}
                                target="_blank"
                                rel="noreferrer"
                                className="block overflow-hidden rounded-md border"
                            >
                                <img
                                    src={photo.thumb_url}
                                    alt={photo.alt ?? ''}
                                    loading="lazy"
                                    className="h-40 w-full object-cover"
                                />
                            </a>
                        ))}
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-3">
                    <ReactionBar
                        url={`/community/${post.ulid}/reactions`}
                        mine={post.my_reaction}
                        counts={post.reaction_counts}
                        total={post.reactions_count}
                    />

                    <div className="flex items-center gap-1">
                        {linked && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={`/community/${post.ulid}`}>
                                    <MessageSquare
                                        className="me-1 size-3.5"
                                        aria-hidden="true"
                                    />
                                    {choice(
                                        'member.community.comments',
                                        post.comments_count,
                                    )}
                                </Link>
                            </Button>
                        )}

                        {post.can.report && (
                            <ReportDialog
                                url={`/community/${post.ulid}/reports`}
                                reasons={reasons}
                            />
                        )}

                        {post.can.delete && (
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
            </CardContent>
        </Card>
    );
}

export function AuthorAvatar({
    author,
    size = 'size-10',
}: {
    author: CommunityAuthor;
    size?: string;
}) {
    const getInitials = useInitials();

    return (
        <Avatar className={size}>
            {author.photo_url && (
                <AvatarImage src={author.photo_url} alt={author.name} />
            )}
            <AvatarFallback>{getInitials(author.name)}</AvatarFallback>
        </Avatar>
    );
}

export function AuthorName({ author }: { author: CommunityAuthor }) {
    // Linked only when the member lets the directory show them. The name is
    // always there — you cannot post under a name nobody may see.
    return author.url ? (
        <Link href={author.url} className="font-medium hover:underline">
            {author.name}
        </Link>
    ) : (
        <span className="font-medium">{author.name}</span>
    );
}
