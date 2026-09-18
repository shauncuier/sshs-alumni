import { Link, router, useForm } from '@inertiajs/react';
import { EyeOff, Flag, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateTime } from '@/lib/format';
import type { ContentReportRow, Option } from '@/types/community';
import type { Paginated } from '@/types/member';

type Props = {
    reports: Paginated<ContentReportRow>;
    filters: { status: string };
    options: { statuses: Option[]; reasons: Option[] };
};

export default function AdminCommunityReports({
    reports,
    filters,
    options,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.community.reports')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.community.reports')}
                    </h1>

                    <div className="flex items-center gap-2">
                        <Select
                            value={filters.status}
                            onValueChange={(value) =>
                                router.get(
                                    '/admin/community/reports',
                                    { status: value },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('admin.community.filter_all')}
                                </SelectItem>
                                {options.statuses.map((status) => (
                                    <SelectItem
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/community/posts">
                                {t('admin.community.posts')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {reports.data.length === 0 ? (
                    <EmptyState
                        icon={ShieldCheck}
                        title={t('admin.community.reports')}
                        description={t('admin.community.reports_empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        {reports.data.map((report) => (
                            <ReportCard key={report.id} report={report} />
                        ))}
                    </div>
                )}

                <Pagination meta={reports.meta} />
            </div>
        </AdminLayout>
    );
}

function ReportCard({ report }: { report: ContentReportRow }) {
    const { t } = useTranslation();
    const [closing, setClosing] = useState<string | null>(null);

    const isOpen = report.status === 'open' || report.status === 'reviewing';

    const moderateItem = (status: string) => {
        if (!report.item) {
            return;
        }

        const url =
            report.item.kind === 'post'
                ? `/admin/community/posts/${report.item.ulid}`
                : `/admin/community/comments/${report.item.id}`;

        router.put(
            url,
            { status, reason: `Report #${report.id}: ${report.reason}` },
            { preserveScroll: true },
        );
    };

    return (
        <Card>
            <CardContent className="space-y-3 pt-6">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0">
                        <p className="flex items-center gap-2 font-medium">
                            <Flag className="size-4" aria-hidden="true" />
                            {report.reason_label}
                        </p>
                        <p className="text-muted-foreground text-xs">
                            {report.reporter
                                ? t('admin.community.reported_by', {
                                      name: report.reporter,
                                  })
                                : t('admin.community.reporter_gone')}
                            {' · '}
                            {formatDateTime(report.created_at)}
                        </p>
                    </div>

                    <Badge variant={isOpen ? 'destructive' : 'outline'}>
                        {report.status_label}
                    </Badge>
                </div>

                {report.note && (
                    <p className="bg-muted rounded-md px-3 py-2 text-sm whitespace-pre-wrap">
                        {report.note}
                    </p>
                )}

                {report.item ? (
                    <div className="rounded-md border p-3">
                        <p className="text-muted-foreground text-xs">
                            {report.item.kind === 'post'
                                ? t('admin.community.posts')
                                : t('member.community.comment')}
                            {' · '}
                            {report.item.author}
                        </p>
                        <p className="mt-1 text-sm whitespace-pre-wrap">
                            {report.item.excerpt}
                        </p>

                        <div className="mt-2 flex flex-wrap items-center gap-1">
                            {report.item.url && (
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={report.item.url}>
                                        {t('admin.community.view_item')}
                                    </Link>
                                </Button>
                            )}

                            {report.item.status === 'published' && (
                                <>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => moderateItem('hidden')}
                                    >
                                        <EyeOff
                                            className="me-1 size-3.5"
                                            aria-hidden="true"
                                        />
                                        {t('admin.community.hide')}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-destructive"
                                        onClick={() => moderateItem('removed')}
                                    >
                                        <Trash2
                                            className="me-1 size-3.5"
                                            aria-hidden="true"
                                        />
                                        {t('admin.community.remove')}
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        {t('admin.community.item_gone')}
                    </p>
                )}

                {report.resolution_note && (
                    <p className="text-muted-foreground text-xs">
                        {report.resolver}
                        {' · '}
                        {formatDateTime(report.resolved_at)}
                        {' — '}
                        {report.resolution_note}
                    </p>
                )}

                {isOpen && (
                    <div className="flex flex-wrap items-center gap-1 border-t pt-3">
                        {report.status === 'open' && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    router.put(
                                        `/admin/community/reports/${report.id}`,
                                        { status: 'reviewing' },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {t('admin.community.mark_reviewing')}
                            </Button>
                        )}

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setClosing('resolved')}
                        >
                            {t('admin.community.resolve')}
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setClosing('dismissed')}
                        >
                            {t('admin.community.dismiss')}
                        </Button>
                    </div>
                )}

                {closing && (
                    <CloseForm
                        reportId={report.id}
                        status={closing}
                        onDone={() => setClosing(null)}
                    />
                )}
            </CardContent>
        </Card>
    );
}

function CloseForm({
    reportId,
    status,
    onDone,
}: {
    reportId: number;
    status: string;
    onDone: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm({ status, note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.put(`/admin/community/reports/${reportId}`, {
            preserveScroll: true,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-2 border-t pt-3">
            <Label htmlFor={`note-${reportId}`}>
                {t('admin.community.resolution_note')}
            </Label>
            <textarea
                id={`note-${reportId}`}
                rows={2}
                value={form.data.note}
                onChange={(event) => form.setData('note', event.target.value)}
                className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
            />
            <p className="text-muted-foreground text-xs">
                {status === 'dismissed'
                    ? t('admin.community.dismiss_help')
                    : t('admin.community.resolution_help')}
            </p>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="ghost" onClick={onDone}>
                    {t('common.actions.cancel')}
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {t('common.actions.confirm')}
                </Button>
            </div>
        </form>
    );
}
