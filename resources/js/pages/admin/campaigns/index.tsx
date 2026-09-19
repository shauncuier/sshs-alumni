import { Link, router } from '@inertiajs/react';
import {
    Clock,
    DollarSign,
    FileText,
    Mail,
    MessageSquare,
    Plus,
    Send,
    Smartphone,
    Trash2,
} from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { formatDate } from '@/lib/format';
import type { Campaign, SmsMetrics } from '@/types/campaign';
import type { Paginated } from '@/types/member';

type Props = {
    campaigns: Paginated<Campaign>;
    filters: { status: string | null; channel: string | null };
    smsMetrics: SmsMetrics;
};

export default function AdminCampaignsIndex({
    campaigns,
    filters,
    smsMetrics,
}: Props) {
    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/admin/campaigns',
            { ...filters, [key]: value || undefined },
            { preserveState: true }
        );
    };

    const handleDelete = (campaign: Campaign) => {
        if (confirm(`Delete draft campaign "${campaign.name}"?`)) {
            router.delete(`/admin/campaigns/${campaign.id}`);
        }
    };

    const statusBadgeVariant = (status: string) => {
        switch (status) {
            case 'completed':
                return 'default';
            case 'sending':
                return 'outline';
            case 'failed':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    return (
        <AdminLayout title="Communication & Campaigns">
            <div className="space-y-6">
                {/* Header & SMS Metrics */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                            Communication Campaigns
                        </h1>
                        <p className="text-sm text-slate-400">
                            Broadcast SMS via BulkSMSBD and transactional email notifications.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Link href="/admin/message-templates">
                            <Button variant="outline" className="border-teal-500/20 bg-slate-900/60 text-teal-300 hover:bg-slate-800">
                                <FileText className="mr-2 h-4 w-4" />
                                Templates
                            </Button>
                        </Link>
                        <Link href="/admin/campaigns/create">
                            <Button className="bg-gradient-to-r from-teal-500 to-teal-600 text-slate-950 font-semibold shadow-lg shadow-teal-500/20 hover:from-teal-400 hover:to-teal-500">
                                <Plus className="mr-2 h-4 w-4" />
                                New Campaign
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* SMS Live Metrics Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="border border-teal-500/10 bg-slate-900/40 backdrop-blur-md">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-slate-400">BulkSMSBD Balance</span>
                                <DollarSign className="h-4 w-4 text-teal-400" />
                            </div>
                            <div className="mt-2 text-xl font-bold text-slate-100">
                                {smsMetrics.balance !== null ? `BDT ${smsMetrics.balance.toFixed(2)}` : 'Demo Mode (Log)'}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                {smsMetrics.enabled ? 'Live gateway active' : 'Test driver (logging only)'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-teal-500/10 bg-slate-900/40 backdrop-blur-md">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-slate-400">Sent Today</span>
                                <Smartphone className="h-4 w-4 text-emerald-400" />
                            </div>
                            <div className="mt-2 text-xl font-bold text-slate-100">
                                {smsMetrics.sentToday} segments
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Reset at midnight UTC+6
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-teal-500/10 bg-slate-900/40 backdrop-blur-md">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-slate-400">Daily Cap Remaining</span>
                                <Clock className="h-4 w-4 text-amber-400" />
                            </div>
                            <div className="mt-2 text-xl font-bold text-slate-100">
                                {smsMetrics.remainingToday}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Cap ceiling: 2,000 / day
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-teal-500/10 bg-slate-900/40 backdrop-blur-md">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-slate-400">Total Campaigns</span>
                                <Send className="h-4 w-4 text-teal-400" />
                            </div>
                            <div className="mt-2 text-xl font-bold text-slate-100">
                                {campaigns.meta.total}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Outbound broadcasts
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <select
                        value={filters.channel ?? ''}
                        onChange={(e) => handleFilterChange('channel', e.target.value)}
                        className="rounded-lg border border-teal-500/20 bg-slate-900/80 px-3 py-2 text-xs text-slate-200 outline-none focus:border-teal-400"
                    >
                        <option value="">All Channels</option>
                        <option value="sms">SMS</option>
                        <option value="mail">Email</option>
                    </select>

                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => handleFilterChange('status', e.target.value)}
                        className="rounded-lg border border-teal-500/20 bg-slate-900/80 px-3 py-2 text-xs text-slate-200 outline-none focus:border-teal-400"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="sending">Sending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                {/* Campaigns List */}
                {campaigns.data.length === 0 ? (
                    <EmptyState
                        icon={MessageSquare}
                        title="No campaigns found"
                        description="Create an SMS or email broadcast to communicate with alumni."
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="border-b border-teal-500/10 bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="px-5 py-3.5">Campaign Name</th>
                                    <th className="px-5 py-3.5">Channel</th>
                                    <th className="px-5 py-3.5">Audience</th>
                                    <th className="px-5 py-3.5">Delivery Status</th>
                                    <th className="px-5 py-3.5">Sent / Total</th>
                                    <th className="px-5 py-3.5">Created</th>
                                    <th className="px-5 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-teal-500/10">
                                {campaigns.data.map((c) => (
                                    <tr key={c.id} className="transition-colors hover:bg-teal-500/[0.03]">
                                        <td className="px-5 py-4">
                                            <Link
                                                href={`/admin/campaigns/${c.id}`}
                                                className="font-semibold text-slate-100 hover:text-teal-400"
                                            >
                                                {c.name}
                                            </Link>
                                            {c.template && (
                                                <div className="mt-0.5 text-xs text-slate-500">
                                                    Template: {c.template.name}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <span className="inline-flex items-center gap-1.5 rounded-full border border-teal-500/20 bg-teal-500/10 px-2.5 py-0.5 text-xs font-medium text-teal-300">
                                                {c.channel === 'sms' ? (
                                                    <Smartphone className="h-3 w-3" />
                                                ) : (
                                                    <Mail className="h-3 w-3" />
                                                )}
                                                {c.channel.toUpperCase()}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4 capitalize text-slate-400">
                                            {c.audience_type.replace('_', ' ')}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge variant={statusBadgeVariant(c.status)} className="capitalize">
                                                {c.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4">
                                            <span className="font-medium text-emerald-400">{c.sent_count}</span>
                                            <span className="text-slate-500"> / {c.recipients_count}</span>
                                            {c.failed_count > 0 && (
                                                <span className="ml-2 text-xs text-rose-400">
                                                    ({c.failed_count} failed)
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-5 py-4 text-xs text-slate-400">
                                            {formatDate(c.created_at)}
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/admin/campaigns/${c.id}`}>
                                                    <Button size="sm" variant="ghost" className="h-8 text-teal-400 hover:bg-teal-500/10">
                                                        View
                                                    </Button>
                                                </Link>
                                                {c.status === 'draft' && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleDelete(c)}
                                                        className="h-8 text-rose-400 hover:bg-rose-500/10"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                <Pagination meta={campaigns.meta} />
            </div>
        </AdminLayout>
    );
}
