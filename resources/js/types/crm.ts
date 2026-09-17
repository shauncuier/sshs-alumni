/**
 * CRM shapes.
 *
 * There is no public counterpart to any of these and there never should be:
 * this is the association's working record of a person.
 *
 * @see app/Http/Resources/CrmContactResource.php
 */

export type Option = { value: string; label: string };

export type Tag = {
    id: number;
    name: string;
    color: string | null;
};

export type TagWithCounts = Tag & {
    description: string | null;
    contacts_count: number;
    members_count: number;
};

export type Contact = {
    ulid: string;
    name: string;
    type: string;
    type_label: string;
    organization_name: string | null;
    designation: string | null;

    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    address: string | null;
    city: string | null;
    district: string | null;
    country: string | null;

    source: string | null;
    relationship_type: string | null;
    pipeline_status: string;
    pipeline_label: string;
    notes: string | null;

    last_activity_at: string | null;
    created_at: string | null;

    owner?: { id: number; name: string } | null;

    /** The alumni record this contact turned out to be. Never duplicated. */
    member?: {
        ulid: string;
        full_name: string;
        membership_no: string | null;
        status_label: string;
    } | null;

    tags?: Tag[];
    open_tasks_count?: number;
};

export type Activity = {
    id: number;
    type: string;
    type_label: string;
    subject_line: string | null;
    body: string | null;
    outcome: string | null;
    occurred_at: string;
    /** Which of the person's two records this was written against. */
    on: 'member' | 'contact';
    user?: string | null;
    meta: Record<string, unknown> | null;
};

export type Task = {
    id: number;
    title: string;
    description: string | null;
    due_at: string | null;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    completed_at: string | null;
    /** Computed server-side: a device with a wrong clock cannot hide a late task. */
    is_overdue: boolean;
    assignee?: { id: number; name: string } | null;
    creator?: string | null;
    subject?: { kind: 'contact' | 'member'; ulid: string; name: string } | null;
};

export type PipelineColumn = {
    stage: string;
    label: string;
    /** Every contact at this stage, ignoring filters. */
    total: number;
    /** How many this column is actually showing. */
    shown: number;
    contacts: Contact[];
};

export type CrmOptions = {
    types: Option[];
    stages: Option[];
    tags: Tag[];
    owners: Option[];
};
