export type CampaignChannelType = 'sms' | 'mail' | 'whatsapp' | 'database';

export type CampaignStatusType =
    | 'draft'
    | 'scheduled'
    | 'sending'
    | 'completed'
    | 'failed'
    | 'cancelled';

export type Campaign = {
    id: number;
    name: string;
    channel: CampaignChannelType;
    subject: string | null;
    subject_bn: string | null;
    body: string;
    body_bn: string | null;
    audience_type: string;
    audience_filters: Record<string, any> | null;
    scheduled_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    status: CampaignStatusType;
    recipients_count: number;
    sent_count: number;
    failed_count: number;
    segments_per_message: number;
    estimated_cost: string | null;
    created_at: string;
    creator?: { id: number; name: string };
    template?: { id: number; name: string };
};

export type CampaignRecipient = {
    id: number;
    campaign_id: number;
    email: string | null;
    phone: string | null;
    status: string;
    sent_at: string | null;
    delivered_at: string | null;
    error: string | null;
    member?: {
        id: number;
        full_name: string;
        batch?: { ssc_year: number };
    };
};

export type MessageTemplate = {
    id: number;
    key: string;
    name: string;
    channel: CampaignChannelType;
    subject: string | null;
    subject_bn: string | null;
    body: string;
    body_bn: string | null;
    variables: string[] | null;
    is_system: boolean;
};

export type SmsMetrics = {
    balance: number | null;
    enabled: boolean;
    sentToday: number;
    remainingToday: number;
};

export type NotificationItem = {
    id: string;
    type: string;
    data: {
        title: string;
        body: string;
        action_url?: string | null;
        action_text?: string | null;
        category?: string;
        icon?: string | null;
    };
    read_at: string | null;
    created_at: string;
};
