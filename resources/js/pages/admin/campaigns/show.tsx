import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Mail,
    Send,
    Smartphone,
} from 'lucide-react';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateTime } from '@/lib/format';
import type { Campaign, CampaignRecipient } from '@/types/campaign';
import type { Paginated } from '@/types/member';

type Props = {
    campaign: Campaign;
    recipients: Paginated<CampaignRecipient>;
    filters: { status: string | null };
    estimate?: {
        is_unicode: boolean;
        characters: number;
        segments: number;
        recipients: number;
        total_segments: number;
    } | null;
};

export default function AdminCampaignShow({
    campaign,
    recipients,
    filters,
    estimate,
}: Props) {
    const handleSend = () => {
        if (
            confirm(
                `Are you sure you want to dispatch this campaign to ${campaign.recipients_count} recipients?`
            )
        ) {
            router.post(`/admin/campaigns/${campaign.id}/send`);
        }
    };

    const statusBadgeVariant = (status: string) => {
        switch (status) {
            case 'sent':
            case 'delivered':
            case 'completed':
                return 'default';
            case 'failed':
                return 'destructive';
            case 'sending':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const percentComplete =
        campaign.recipients_count > 0
            ? Math.round(
                  ((campaign.sent_count + campaign.failed_count) /
                      campaign.recipients_count) *
                      100
              )
            : 0;

    return (
        <AdminLayout title={`Campaign: ${campaign.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Link href="/admin/campaigns">
                        <Button variant="ghost" size="sm" className="gap-2 text-slate-400 hover:text-slate-100">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Campaigns
                        </Button>
                    </Link>

                    {campaign.status === 'draft' && (
                        <Button
                            onClick={handleSend}
                            disabled={campaign.recipients_count === 0}
                            className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 shadow-lg shadow-teal-500/20 hover:from-teal-400 hover:to-teal-500"
                        >
                            <Send className="mr-2 h-4 w-4" />
                            Dispatch Campaign Now
                        </Button>
                    )}
                </div>

                {/* Campaign Header Card */}
                <Card className="border border-teal-500/15 bg-slate-900/60 backdrop-blur-md">
                    <CardContent className="p-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-100">
                                        {campaign.name}
                                    </h1>
                                    <Badge variant={statusBadgeVariant(campaign.status)} className="capitalize">
                                        {campaign.status}
                                    </Badge>
                                </div>
                                <div className="mt-1 flex flex-wrap items-center gap-4 text-xs text-slate-400">
                                    <span className="flex items-center gap-1">
                                        {campaign.channel === 'sms' ? (
                                            <Smartphone className="h-3.5 w-3.5 text-teal-400" />
                                        ) : (
                                            <Mail className="h-3.5 w-3.5 text-teal-400" />
                                        )}
                                        {campaign.channel.toUpperCase()}
                                    </span>
                                    <span>Audience: <strong className="text-slate-200 capitalize">{campaign.audience_type.replace('_', ' ')}</strong></span>
                                    <span>Created: {formatDateTime(campaign.created_at)}</span>
                                </div>
                            </div>

                            {/* SMS Segment & Cost Card */}
                            {estimate && (
                                <div className="rounded-xl border border-teal-500/20 bg-slate-950/80 p-3 text-right">
                                    <div className="text-xs text-slate-400">Estimated Delivery Cost</div>
                                    <div className="text-lg font-bold text-teal-300">
                                        ~BDT {(estimate.total_segments * 0.35).toFixed(2)}
                                    </div>
                                    <div className="text-[11px] text-slate-500">
                                        {estimate.total_segments} total segments ({estimate.is_unicode ? 'Unicode' : 'GSM-7'})
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Dispatch Progress Bar */}
                        <div className="mt-6 space-y-2">
                            <div className="flex justify-between text-xs text-slate-400">
                                <span>Progress: {percentComplete}%</span>
                                <span>
                                    {campaign.sent_count} sent • {campaign.failed_count} failed • {campaign.recipients_count} total
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-slate-950">
                                <div
                                    className="h-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-500"
                                    style={{ width: `${percentComplete}%` }}
                                />
                            </div>
                        </div>

                        {/* Message Preview */}
                        <div className="mt-6 rounded-xl border border-teal-500/15 bg-slate-950/60 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wider text-teal-400">
                                {campaign.subject ? `Subject: ${campaign.subject}` : 'Message Body Preview'}
                            </div>
                            <div className="mt-2 text-sm text-slate-300 whitespace-pre-wrap font-sans">
                                {campaign.body}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Recipients List */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-100">
                            Recipient Delivery Log ({recipients.meta.total})
                        </h2>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="border-b border-teal-500/10 bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="px-5 py-3.5">Recipient</th>
                                    <th className="px-5 py-3.5">Contact Detail</th>
                                    <th className="px-5 py-3.5">Status</th>
                                    <th className="px-5 py-3.5">Timestamp</th>
                                    <th className="px-5 py-3.5">Note / Error</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-teal-500/10">
                                {recipients.data.map((r) => (
                                    <tr key={r.id} className="transition-colors hover:bg-teal-500/[0.03]">
                                        <td className="px-5 py-4">
                                            <div className="font-medium text-slate-100">
                                                {r.member?.full_name ?? 'Alumni Contact'}
                                            </div>
                                            {r.member?.batch && (
                                                <div className="text-xs text-slate-500">
                                                    Batch {r.member.batch.ssc_year}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4 font-mono text-xs text-slate-400">
                                            {r.phone || r.email || '—'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge variant={statusBadgeVariant(r.status)} className="capitalize">
                                                {r.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4 text-xs text-slate-400">
                                            {r.sent_at ? formatDateTime(r.sent_at) : '—'}
                                        </td>
                                        <td className="px-5 py-4 text-xs text-rose-300">
                                            {r.error || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    <Pagination meta={recipients.meta} />
                </div>
            </div>
        </AdminLayout>
    );
}
