/**
 * Shapes returned by the community resources.
 *
 * `photo_url` and `url` on an author are OPTIONAL because the server omits
 * them entirely for a member who has hidden their profile — the name is always
 * there (you cannot post under a name nobody may see), the face and the link
 * are not.
 *
 * @see app/Http/Resources/PostResource.php
 */

export type Option = { value: string; label: string };

export type CommunityAuthor = {
    ulid: string;
    name: string;
    batch?: string | null;

    // Present only when the member lets the directory show them.
    photo_url?: string | null;
    url?: string;
};

export type PostPhoto = {
    url: string;
    thumb_url: string;
    alt: string | null;
};

export type CommunityPost = {
    ulid: string;
    category: string;
    category_label: string;
    batch?: string | null;
    title: string | null;
    body: string;
    status: string;
    status_label: string;
    is_pinned: boolean;
    comments_enabled: boolean;
    comments_count: number;
    reactions_count: number;
    created_at: string | null;
    last_activity_at: string | null;
    author: CommunityAuthor;
    photos?: PostPhoto[];
    my_reaction?: string | null;
    reaction_counts?: Record<string, number>;
    can: {
        update: boolean;
        delete: boolean;
        report: boolean;
    };
};

export type CommunityComment = {
    id: number;
    parent_id: number | null;
    body: string;
    status: string;
    created_at: string | null;
    author: CommunityAuthor;
    replies?: CommunityComment[];
    reactions_count?: number;
    my_reaction?: string | null;
    reaction_counts?: Record<string, number>;
    can: {
        delete: boolean;
        report: boolean;
    };
};

/**
 * Every `@member:{ulid}` in the page's bodies, resolved server-side once.
 *
 * `url` is null for a member who has hidden their profile: their name still
 * renders, it just is not a door into a profile they closed.
 */
export type MentionMap = Record<
    string,
    { ulid: string; name: string; url: string | null }
>;

export type ModeratedPost = {
    ulid: string;
    title: string | null;
    excerpt: string;
    category: string;
    category_label: string;
    batch: string | null;
    status: string;
    status_label: string;
    is_pinned: boolean;
    comments_enabled: boolean;
    comments_count: number;
    reactions_count: number;
    open_reports_count: number;
    deleted: boolean;
    author: string;
    author_ulid: string;
    created_at: string | null;
    url: string;
};

export type ReportedItem = {
    kind: 'post' | 'comment';
    id: number;
    ulid: string | null;
    excerpt: string;
    status: string;
    author: string;
    url: string | null;
};

export type ContentReportRow = {
    id: number;
    reason: string;
    reason_label: string;
    note: string | null;
    status: string;
    status_label: string;
    reporter: string | null;
    resolver: string | null;
    resolved_at: string | null;
    resolution_note: string | null;
    created_at: string | null;
    item: ReportedItem | null;
};
