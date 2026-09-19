import { router } from '@inertiajs/react';
import {
    Clock,
    FileJson,
    Filter,
    RotateCcw,
    ScrollText,
    Shield,
    User,
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types/member';

type AuditLogItem = {
    id: number;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    before: Record<string, any> | null;
    after: Record<string, any> | null;
    description: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
};

type Props = {
    logs: Paginated<AuditLogItem>;
    filters: {
        action: string | null;
        user_id: string | null;
        auditable_type: string | null;
        date_from: string | null;
        date_to: string | null;
    };
    availableActions: string[];
    staffUsers: Array<{ id: number; name: string; email: string }>;
};

export default function AdminAuditIndex({
    logs,
    filters,
    availableActions,
    staffUsers,
}: Props) {
    const [actionFilter, setActionFilter] = useState(filters.action || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const [inspectLog, setInspectLog] = useState<AuditLogItem | null>(null);

    const handleApplyFilters = () => {
        router.get(
            '/admin/audit-logs',
            {
                action: actionFilter || undefined,
                user_id: userFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true }
        );
    };

    const handleReset = () => {
        setActionFilter('');
        setUserFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/admin/audit-logs');
    };

    const actionBadgeColor = (action: string) => {
        if (action.includes('created') || action.includes('approved')) {
            return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30';
        }
        if (action.includes('deleted') || action.includes('rejected') || action.includes('disabled')) {
            return 'bg-rose-500/15 text-rose-400 border-rose-500/30';
        }
        if (action.includes('updated') || action.includes('export')) {
            return 'bg-amber-500/15 text-amber-400 border-amber-500/30';
        }
        return 'bg-teal-500/15 text-teal-400 border-teal-500/30';
    };

    return (
        <AdminLayout title="Audit Trail">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <Shield className="h-5 w-5 text-teal-400" />
                            <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                                Audit Logs Explorer
                            </h1>
                        </div>
                        <p className="mt-1 text-sm text-slate-400">
                            Append-only security and compliance trail of administrative activities and model mutations.
                        </p>
                    </div>
                </div>

                {/* Filter Bar */}
                <div className="rounded-xl border border-teal-500/15 bg-slate-900/40 p-4 backdrop-blur-md">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label className="mb-1 block text-xs text-slate-400">Action</label>
                            <input
                                list="actions-list"
                                value={actionFilter}
                                onChange={(e) => setActionFilter(e.target.value)}
                                placeholder="Filter by action..."
                                className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-1.5 text-xs text-slate-200 outline-none focus:border-teal-400"
                            />
                            <datalist id="actions-list">
                                {availableActions.map((a) => (
                                    <option key={a} value={a} />
                                ))}
                            </datalist>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-slate-400">Actor / User</label>
                            <select
                                value={userFilter}
                                onChange={(e) => setUserFilter(e.target.value)}
                                className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-1.5 text-xs text-slate-200 outline-none focus:border-teal-400"
                            >
                                <option value="">All Actors</option>
                                {staffUsers.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name} ({u.email})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-slate-400">Date From</label>
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="h-8 border-teal-500/20 bg-slate-950 text-xs text-slate-200"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs text-slate-400">Date To</label>
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="h-8 border-teal-500/20 bg-slate-950 text-xs text-slate-200"
                            />
                        </div>

                        <div className="flex items-end gap-2">
                            <Button
                                size="sm"
                                onClick={handleApplyFilters}
                                className="h-8 flex-1 bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400"
                            >
                                <Filter className="mr-1 h-3.5 w-3.5" />
                                Filter
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={handleReset}
                                className="h-8 text-slate-400 hover:text-slate-200"
                            >
                                <RotateCcw className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Audit Logs Table */}
                {logs.data.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title="No audit entries found"
                        description="No audit trail events match your current filter parameters."
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="border-b border-teal-500/10 bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="px-5 py-3.5">Timestamp</th>
                                    <th className="px-5 py-3.5">Actor</th>
                                    <th className="px-5 py-3.5">Action</th>
                                    <th className="px-5 py-3.5">Entity</th>
                                    <th className="px-5 py-3.5">IP Address</th>
                                    <th className="px-5 py-3.5 text-right">Details</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-teal-500/10">
                                {logs.data.map((log) => (
                                    <tr key={log.id} className="transition-colors hover:bg-teal-500/[0.03]">
                                        <td className="px-5 py-3.5 text-xs text-slate-400 whitespace-nowrap">
                                            <div className="flex items-center gap-1.5">
                                                <Clock className="h-3.5 w-3.5 text-slate-500" />
                                                <span>{log.created_at ? formatDateTime(log.created_at) : '—'}</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-3.5">
                                            {log.user ? (
                                                <div>
                                                    <div className="font-medium text-slate-200">{log.user.name}</div>
                                                    <div className="text-[11px] text-slate-500">{log.user.email}</div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-slate-500 italic">System / CLI</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5">
                                            <span className={`inline-flex rounded-md border px-2 py-0.5 text-xs font-mono font-medium ${actionBadgeColor(log.action)}`}>
                                                {log.action}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3.5 text-xs text-slate-400">
                                            {log.auditable_type ? (
                                                <span>
                                                    <span className="text-slate-200">{log.auditable_type}</span>
                                                    {log.auditable_id && <span className="text-slate-500"> #{log.auditable_id}</span>}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5 text-xs font-mono text-slate-400">
                                            {log.ip_address || '—'}
                                        </td>
                                        <td className="px-5 py-3.5 text-right">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => setInspectLog(log)}
                                                className="h-7 text-xs text-teal-400 hover:bg-teal-500/10"
                                            >
                                                <FileJson className="mr-1 h-3.5 w-3.5" />
                                                Inspect
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                <Pagination meta={logs.meta} />
            </div>

            {/* Inspection Modal */}
            <Dialog open={inspectLog !== null} onOpenChange={(open) => !open && setInspectLog(null)}>
                <DialogContent className="max-w-2xl border border-teal-500/20 bg-slate-900 text-slate-100">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-semibold">
                            <ScrollText className="h-4 w-4 text-teal-400" />
                            Audit Log Entry #{inspectLog?.id}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-400">
                            Recorded at {inspectLog?.created_at ? formatDateTime(inspectLog.created_at) : ''}
                        </DialogDescription>
                    </DialogHeader>

                    {inspectLog && (
                        <div className="space-y-4 text-xs">
                            <div className="grid grid-cols-2 gap-3 rounded-lg border border-teal-500/10 bg-slate-950/60 p-3">
                                <div>
                                    <span className="text-slate-500">Action:</span>{' '}
                                    <code className="text-teal-400">{inspectLog.action}</code>
                                </div>
                                <div>
                                    <span className="text-slate-500">Actor:</span>{' '}
                                    <span className="text-slate-200">{inspectLog.user?.name || 'System'}</span>
                                </div>
                                <div>
                                    <span className="text-slate-500">Target:</span>{' '}
                                    <span className="text-slate-200">
                                        {inspectLog.auditable_type || 'None'} {inspectLog.auditable_id ? `#${inspectLog.auditable_id}` : ''}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500">IP / Agent:</span>{' '}
                                    <span className="text-slate-400 truncate block">{inspectLog.ip_address || '—'}</span>
                                </div>
                            </div>

                            {inspectLog.description && (
                                <div>
                                    <div className="mb-1 font-semibold text-slate-300">Description</div>
                                    <div className="rounded-md border border-slate-800 bg-slate-950 p-2 text-slate-300">
                                        {inspectLog.description}
                                    </div>
                                </div>
                            )}

                            {inspectLog.before && (
                                <div>
                                    <div className="mb-1 font-semibold text-rose-400">Previous State (Before)</div>
                                    <pre className="max-h-40 overflow-auto rounded-md border border-rose-500/20 bg-slate-950 p-3 text-[11px] text-slate-300">
                                        {JSON.stringify(inspectLog.before, null, 2)}
                                    </pre>
                                </div>
                            )}

                            {inspectLog.after && (
                                <div>
                                    <div className="mb-1 font-semibold text-emerald-400">Changed State (After)</div>
                                    <pre className="max-h-40 overflow-auto rounded-md border border-emerald-500/20 bg-slate-950 p-3 text-[11px] text-slate-300">
                                        {JSON.stringify(inspectLog.after, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
