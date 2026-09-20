import { router } from '@inertiajs/react';
import { Briefcase, CheckCircle, Search, Trash2, XCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/types/member';

type Job = {
    id: number;
    ulid: string;
    title: string;
    company_name: string;
    location: string | null;
    workplace_type: string;
    employment_type: string;
    salary_range: string | null;
    deadline_at: string | null;
    status: string;
    member: {
        ulid: string;
        full_name: string;
        membership_no: string | null;
    } | null;
    created_at: string | null;
};

type Props = {
    jobs: Paginated<Job>;
    filters: Record<string, string | null>;
    statuses: string[];
};

export default function AdminJobsIndex({ jobs, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/jobs', { q: q || undefined }, { preserveState: true });
    };

    const handleTogglePublish = (ulid: string) => {
        router.put(`/admin/jobs/${ulid}/publish`, {}, { preserveScroll: true });
    };

    const handleDelete = (ulid: string) => {
        if (confirm('Are you sure you want to delete this job posting?')) {
            router.delete(`/admin/jobs/${ulid}`);
        }
    };

    return (
        <AdminLayout title="Career & Job Board">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Career & Job Board
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Moderate, monitor, and manage career opportunities posted by alumni network members.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSearch} className="flex gap-2 max-w-md">
                    <Input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search by job title or company..."
                        className="rounded-xl"
                    />
                    <Button type="submit" variant="secondary" className="rounded-xl">
                        <Search className="size-4" />
                    </Button>
                </form>

                {jobs.data.length === 0 ? (
                    <EmptyState
                        icon={Briefcase}
                        title="No job postings found"
                        description="No job postings currently match your search criteria."
                    />
                ) : (
                    <div className="grid gap-4">
                        {jobs.data.map((j) => (
                            <Card key={j.ulid} className="border-border/70">
                                <CardContent className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div className="space-y-1.5 flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h2 className="font-bold text-base text-foreground truncate">
                                                {j.title}
                                            </h2>
                                            <Badge variant="outline">{j.employment_type}</Badge>
                                            <Badge variant={j.status === 'published' ? 'default' : 'secondary'}>
                                                {j.status}
                                            </Badge>
                                        </div>

                                        <p className="text-sm font-semibold text-teal-700 dark:text-teal-400">
                                            {j.company_name} {j.location && `· ${j.location}`} ({j.workplace_type})
                                        </p>

                                        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground pt-1">
                                            {j.member && (
                                                <span>
                                                    Posted by: <strong className="text-foreground">{j.member.full_name}</strong>
                                                </span>
                                            )}
                                            {j.salary_range && <span>Salary: {j.salary_range}</span>}
                                            {j.deadline_at && <span>Deadline: {formatDate(j.deadline_at)}</span>}
                                            {j.created_at && <span>Posted: {formatDate(j.created_at)}</span>}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleTogglePublish(j.ulid)}
                                            className="rounded-xl gap-1"
                                        >
                                            {j.status === 'published' ? (
                                                <>
                                                    <XCircle className="size-3.5 text-amber-500" />
                                                    Unpublish
                                                </>
                                            ) : (
                                                <>
                                                    <CheckCircle className="size-3.5 text-emerald-600" />
                                                    Publish
                                                </>
                                            )}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleDelete(j.ulid)}
                                            className="text-destructive hover:bg-destructive/10 rounded-xl"
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={jobs.meta} />
            </div>
        </AdminLayout>
    );
}
