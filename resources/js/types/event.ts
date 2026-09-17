/**
 * Event shapes.
 *
 * THE DATE RULE is in the type. `starts_at` is optional because the server
 * OMITS it entirely while `date_status` is `tba` — so TypeScript will not let
 * a component read a date the committee has not announced.
 *
 * @see app/Http/Resources/PublicEventResource.php
 * @see docs/17-golden-jubilee.md section 2
 */

export type TicketType = {
    id: number;
    name: string;
    description: string | null;
    price: number;
    currency: string;
    per_person_limit: number;
    /** Whether any remain. Never how many — a countdown manufactures urgency. */
    available: boolean;
};

export type AdminTicketType = Omit<TicketType, 'available'> & {
    quantity: number | null;
    sold_count: number;
    is_active: boolean;
    display_order: number;
};

export type PublicEvent = {
    ulid: string;
    slug: string;
    type: string;
    type_label: string;
    title: string;
    summary: string | null;
    description: string | null;
    cover_url: string | null;

    date_status: 'tba' | 'announced';
    /** Present ONLY when date_status is 'announced'. */
    starts_at?: string | null;
    ends_at?: string | null;

    venue: string | null;
    address: string | null;
    map_url: string | null;

    status: string;
    status_label: string;
    is_flagship: boolean;
    registration_required: boolean;
    registration_fee: number | null;
    currency: string;
    organizer_name: string | null;

    ticket_types?: TicketType[];
};

export type AdminEvent = Omit<PublicEvent, 'ticket_types'> & {
    id: number;
    date_is_tba: boolean;
    /** The admin panel DOES see an unannounced draft date — that is where it is set. */
    starts_at: string | null;
    ends_at: string | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    capacity: number | null;
    contact_phone: string | null;
    contact_email: string | null;
    published_at: string | null;
    registrations_count?: number;
    checkins_count?: number;
    ticket_types?: AdminTicketType[];
};

export type RegistrationGuest = {
    name: string;
    relation: string | null;
    age_group: string | null;
};

export type Registration = {
    ulid: string;
    registrant_name: string;
    registrant_email: string | null;
    registrant_phone: string | null;
    guests_count: number;
    seats: number;
    amount_due: number;
    currency: string;
    payment_status: string;
    payment_status_label: string;
    status: string;
    status_label: string;
    registered_at: string | null;
    member_ulid?: string | null;
    ticket_type?: string | null;
    event?: PublicEvent;
    guests?: RegistrationGuest[];
    checkin?: {
        checked_in_at: string;
        gate: string | null;
        operator: string | null;
    } | null;
};

export type CheckinStats = {
    expected: number;
    checked_in: number;
    remaining: number;
};

export type RecentCheckin = {
    name: string | null;
    checked_in_at: string;
    gate: string | null;
    operator: string | null;
};

export type Seats = {
    capacity: number | null;
    taken: number;
    /** null means the event has no capacity limit. */
    left: number | null;
};
