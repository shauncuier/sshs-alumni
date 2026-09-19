import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    FileSpreadsheet,
    ShieldAlert,
} from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    reportKey: string;
    reportInfo: {
        title: string;
        description: string;
        category: string;
    };
    headers: string[];
    rows: Array<Record<string, any>>;
    filters: Record<string, any>;
};

export default function AdminReportShow({
    reportKey,
    reportInfo,
    headers,
    rows,
    filters,
}: Props) {
    return (
        <AdminLayout title={reportInfo.title}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <Link href="/admin/reports">
                                <Button variant="ghost" size="sm" className="gap-1 text-slate-400 hover:text-slate-100">
                                    <ArrowLeft className="h-4 w-4" />
                                    Reports Catalog
                                </Button>
                            </Link>
                        </div>
                        <div className="mt-2 flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                                {reportInfo.title}
                            </h1>
                            <Badge variant="outline" className="border-teal-500/30 text-teal-400">
                                {reportInfo.category}
                            </Badge>
                        </div>
                        <p className="mt-1 text-sm text-slate-400">
                            {reportInfo.description} (Previewing up to {rows.length} rows)
                        </p>
                    </div>

                    <form action={`/admin/reports/${reportKey}/export`} method="POST">
                        <input
                            type="hidden"
                            name="_token"
                            value={(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''}
                        />
                        {Object.entries(filters).map(([k, v]) => (
                            <input key={k} type="hidden" name={k} value={v} />
                        ))}
                        <Button className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 hover:from-teal-400 hover:to-teal-500 shadow-md shadow-teal-500/10">
                            <Download className="mr-2 h-4 w-4" />
                            Download Full CSV ({rows.length} records)
                        </Button>
                    </form>
                </div>

                {/* Audit Guard Notice */}
                <div className="flex items-center gap-3 rounded-lg border border-teal-500/20 bg-slate-900/60 p-3 text-xs text-slate-300">
                    <ShieldAlert className="h-4 w-4 shrink-0 text-teal-400" />
                    <span>
                        <strong>Audited Data Access:</strong> Every export operation is permanently recorded in the system audit log with your administrative user ID, timestamp, and filter criteria.
                    </span>
                </div>

                {/* Data Preview Table */}
                {rows.length === 0 ? (
                    <EmptyState
                        icon={FileSpreadsheet}
                        title="No records found"
                        description="There are currently no rows matching the criteria for this report."
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <div className="max-h-[600px] overflow-auto">
                            <table className="w-full text-left text-sm text-slate-300">
                                <thead className="sticky top-0 z-10 border-b border-teal-500/10 bg-slate-950 text-xs uppercase tracking-wider text-slate-400">
                                    <tr>
                                        {headers.map((header, idx) => (
                                            <th key={idx} className="px-4 py-3.5 whitespace-nowrap">
                                                {header}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-teal-500/10">
                                    {rows.map((row, rowIdx) => (
                                        <tr key={rowIdx} className="transition-colors hover:bg-teal-500/[0.03]">
                                            {Object.values(row).map((val, colIdx) => (
                                                <td key={colIdx} className="px-4 py-3 text-xs text-slate-200 whitespace-nowrap">
                                                    {val !== null && val !== undefined ? String(val) : '—'}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
