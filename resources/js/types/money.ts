/**
 * Money and people shapes.
 *
 * @see app/Http/Resources/PaymentResource.php
 */

export type Option = { value: string; label: string };

export type Payment = {
    ulid: string;
    reference: string;
    receipt_no: string | null;
    invoice_no: string | null;
    payer_name: string;
    amount: number;
    currency: string;
    gateway: string;
    gateway_txn_id: string | null;
    status: string;
    status_label: string;
    paid_at: string | null;
    refunded_at: string | null;
    refund_reason: string | null;
    notes: string | null;
    meta: Record<string, unknown> | null;
    created_at: string | null;
    /** What the money was for, in words. */
    for?: string | null;
    payer_member_ulid?: string | null;
    recorded_by?: string | null;
};

export type LedgerTotals = {
    received: number;
    pending: number;
    refunded: number;
    currency: string;
};

export type Fee = {
    id: number;
    period_label: string;
    amount: number;
    currency: string;
    due_at: string | null;
    status: string;
    status_label: string;
    waived: boolean;
    waived_reason: string | null;
    is_overdue: boolean;
    member: {
        ulid: string | null;
        full_name: string | null;
        membership_no: string | null;
    };
};

export type Donation = {
    ulid: string;
    donor_name: string;
    amount: number;
    currency: string;
    campaign: string | null;
    status: string;
    status_label: string;
    is_anonymous: boolean;
    is_public: boolean;
    message: string | null;
    received_at: string | null;
    created_at: string | null;
    member_ulid?: string | null;
    event?: string | null;
};

export type SponsorshipPackage = {
    id: number;
    name: string;
    tier: string;
    tier_label: string;
    amount: number | null;
    currency: string;
    benefits: string | null;
    max_slots: number | null;
    is_active: boolean;
    taken: number;
};

export type Sponsor = {
    ulid: string;
    name: string;
    kind: string;
    kind_label: string;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    website: string | null;
    logo_url: string | null;
    amount: number | null;
    currency: string;
    status: string;
    status_label: string;
    is_public: boolean;
    display_order: number;
    package: string | null;
    tier_label: string | null;
    event: string | null;
    notes: string | null;
};

export type Assignment = {
    id: number;
    team: string | null;
    event: string | null;
    responsibility: string | null;
    status: string;
    status_label: string;
};

export type Volunteer = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    availability: string | null;
    location: string | null;
    status: string;
    status_label: string;
    notes: string | null;
    member_ulid: string | null;
    assignments: Assignment[];
};

export type VolunteerTeam = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    assignments_count: number;
    lead: string | null;
};

export type CommitteePerson = {
    id: number;
    name: string;
    role: string;
    designation: string | null;
    photo_url: string | null;
    status: string;
    status_label: string;
    member_ulid: string | null;
};

export type Committee = {
    id: number;
    slug: string;
    name: string;
    type: string;
    type_label: string;
    description: string | null;
    term_start: string | null;
    term_end: string | null;
    status: string;
    members: CommitteePerson[];
};
