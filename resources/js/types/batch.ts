/**
 * Batch shapes.
 *
 * @see app/Http/Controllers/Admin/BatchController.php
 * @see app/Http/Controllers/Member/BatchController.php
 */

export type Option = { value: string; label: string };

/** A coordinator, as both the admin and member surfaces render them. */
export type BatchCoordinator = {
    ulid: string;
    name: string;
    membership_no: string | null;
    photo_url: string | null;
};

export type AdminBatchRow = {
    id: number;
    slug: string;
    name: string;
    ssc_year: number;
    members_count: number;
    status: string;
    status_label: string;
    coordinators: BatchCoordinator[];
};

export type AdminBatchDetail = AdminBatchRow & {
    description: string | null;
    cover_url: string | null;
};

/** The member-facing view of their own cohort. */
export type MemberBatch = {
    slug: string;
    name: string;
    description: string | null;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};
