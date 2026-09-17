/**
 * Shapes returned by the member resources.
 *
 * Privacy-governed fields are OPTIONAL on purpose: the server omits the key
 * entirely when a member has hidden it, so the type mirrors reality rather
 * than pretending the field is always present.
 *
 * @see app/Http/Resources/DirectoryMemberResource.php
 */

export type MemberLink = {
    type: string;
    label: string;
    url: string;
};

export type DirectoryMember = {
    ulid: string;
    full_name: string;
    full_name_bn: string | null;
    photo_url: string | null;
    relation_type: string;
    relation_label: string;
    membership_no: string | null;
    ssc_year: number | null;
    batch?: string | null;
    bio: string | null;
    links?: MemberLink[];

    // Present only when the member shares them.
    occupation?: string | null;
    organization?: string | null;
    job_title?: string | null;
    industry?: string | null;
    city?: string | null;
    district?: string | null;
    division?: string | null;
    country?: string | null;
    mobile?: string | null;
    whatsapp?: string | null;
    email?: string | null;
};

export type PublicMember = {
    full_name: string;
    full_name_bn: string | null;
    photo_url: string | null;
    membership_no: string | null;
    batch?: string | null;
    status: string;
    status_label: string;
    is_verified: boolean;
    verified_at: string | null;
};

export type VerificationEntry = {
    id: number;
    from_status: string | null;
    from_label: string | null;
    to_status: string;
    to_label: string;
    note: string | null;
    correction_requested: string | null;
    actor?: string | null;
    created_at: string | null;
};

export type AdminMember = DirectoryMember & {
    id: number;
    date_of_birth: string | null;
    gender: string | null;
    gender_label: string | null;
    blood_group: string | null;
    batch_id: number | null;
    student_id: string | null;
    admission_year: number | null;
    group_stream: string | null;
    section: string | null;
    house: string | null;
    higher_education: string | null;
    business_info: string | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    bio_bn: string | null;
    skills: string[] | null;
    interests: string[] | null;
    status: string;
    status_label: string;
    verified_at: string | null;
    verified_by?: string | null;
    registered_at: string | null;
    profile_completion: number;
    privacy?: Record<string, boolean>;
    verifications?: VerificationEntry[];
    user?: {
        email: string;
        email_verified_at: string | null;
        last_active_at: string | null;
    } | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    last_page: number;
    /**
     * The numbered page links. Laravel puts these INSIDE meta; the top-level
     * `links` of a paginated resource is a {first,last,prev,next} object, not
     * an array, so reading page links from there breaks as soon as a list has
     * a second page.
     */
    links: PaginationLink[];
};

export type Paginated<T> = {
    data: T[];
    meta: PaginationMeta;
};
