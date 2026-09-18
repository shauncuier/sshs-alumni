/**
 * Shapes the CMS controllers return.
 *
 * @see app/Http/Controllers/Public/ContentController.php
 */

export type Option = { value: string; label: string };

export type NewsCard = {
    slug: string;
    title: string;
    excerpt: string | null;
    category?: string | null;
    is_featured?: boolean;
    published_at: string | null;
    views_count?: number;
    cover_url: string | null;
    url: string;
};

export type NewsArticle = NewsCard & {
    body: string;
    author: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

export type AnnouncementCard = {
    id: number;
    kind: string;
    kind_label?: string;
    level: string;
    level_label?: string;
    title: string;
    body: string;
    batch?: string | null;
    starts_at: string | null;
    ends_at?: string | null;
    is_pinned?: boolean;
    attachment_url?: string | null;
};

export type AlbumCard = {
    slug: string;
    title: string;
    description: string | null;
    images_count: number;
    event: string | null;
    batch: string | null;
    published_at: string | null;
    cover_url: string | null;
    url: string;
};

export type AlbumImage = {
    id: number;
    caption: string | null;
    url: string;
    thumb_url: string;
    width?: number | null;
    height?: number | null;
};

export type StoryCard = {
    slug: string;
    title: string;
    author_name: string;
    batch?: string | null;
    career_summary: string | null;
    excerpt: string;
    is_featured?: boolean;
    published_at: string | null;
    photo_url: string | null;
    url: string;
};

export type Story = StoryCard & {
    body: string;
    meta_description: string | null;
};

export type Milestone = {
    id?: number;
    year: number;
    date_label?: string | null;
    title: string;
    description: string | null;
    is_highlighted?: boolean;
    image_url?: string | null;
};

export type Faq = {
    id: number;
    group: string;
    group_label: string;
    question: string;
    answer: string;
};

// ── Admin shapes ─────────────────────────────────────────────────────────

export type AdminNews = NewsCard & {
    id: number;
    body: string;
    status: string;
    status_label: string;
    author: string | null;
    meta_title: string | null;
    meta_description: string | null;
    summary: string;
};

export type AdminAnnouncement = {
    id: number;
    kind: string;
    kind_label: string;
    title: string;
    body: string;
    level: string;
    level_label: string;
    audience: string;
    audience_label: string;
    batch_id: number | null;
    batch: string | null;
    starts_at: string | null;
    ends_at: string | null;
    is_pinned: boolean;
    status: string;
    status_label: string;
    published_by: string | null;
    is_live: boolean;
};

export type AdminPage = {
    id: number;
    slug: string;
    title: string;
    body: string | null;
    status: string;
    status_label: string;
    is_system: boolean;
    published_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
    updated_by: string | null;
    url: string;
};

export type AdminAlbum = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    event_id: number | null;
    event: string | null;
    batch_id: number | null;
    batch: string | null;
    status: string;
    status_label: string;
    published_at: string | null;
    images_count: number;
    display_order: number;
    cover_url: string | null;
    url: string;
};

export type AdminAlbumImage = {
    id: number;
    caption: string | null;
    display_order: number;
    url: string;
    thumb_url: string;
};

export type AdminStory = {
    id: number;
    slug: string;
    title: string;
    body: string;
    excerpt: string;
    author_name: string;
    member_ulid: string | null;
    batch: string | null;
    career_summary: string | null;
    is_featured: boolean;
    status: string;
    status_label: string;
    published_at: string | null;
    submitted_at: string | null;
    reviewer: string | null;
    photo_url: string | null;
    meta_description: string | null;
    url: string | null;
};

export type AdminFaq = {
    id: number;
    group: string;
    group_label: string;
    question: string;
    answer: string;
    display_order: number;
    is_published: boolean;
};

export type MediaItem = {
    id: number;
    collection: string;
    collection_label: string;
    original_name: string;
    mime_type: string;
    size: number;
    width: number | null;
    height: number | null;
    is_public: boolean;
    uploaded_by: string | null;
    created_at: string | null;
    in_use: boolean;
    /** Null for private media, by design — it is served through a policy check. */
    url: string | null;
    thumb_url: string | null;
};

export type MemberStory = {
    id: number;
    title: string;
    body: string;
    career_summary: string | null;
    status: string;
    status_label: string;
    submitted_at: string | null;
    published_at: string | null;
    photo_url: string | null;
    editable: boolean;
    url: string | null;
};
