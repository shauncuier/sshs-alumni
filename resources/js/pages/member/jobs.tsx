import { Briefcase, MapPin, Search, Calendar, DollarSign, ExternalLink } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import MemberLayout from '@/layouts/member-layout';
import type { Paginated } from '@/types/member';

type Job = {
    ulid: string;
    title: string;
    company_name: string;
    location: string | null;
    workplace_type: string;
    employment_type: string;
    experience_level: string | null;
    salary_range: string | null;
    description: string;
    application_url_or_email: string;
    deadline_at: string | null;
    poster?: {
        full_name: string;
        batch?: string | null;
    } | null;
};

type Props = {
    jobs: Paginated<Job>;
    filters: {
        q?: string | null;
        workplace_type?: string | null;
        employment_type?: string | null;
    };
};

export default function MemberJobs({ jobs, filters }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/jobs', { ...filters, q: search }, { preserveState: true });
    };

    return (
        <MemberLayout title="Alumni Job & Career Board">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Alumni Job & Career Board</h1>
                        <p className="text-muted-foreground text-sm">
                            Career opportunities shared exclusively within the SSHS alumni network.
                        </p>
                    </div>

                    <form onSubmit={handleSearch} className="flex max-w-sm gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search jobs, companies..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" size="sm">Search</Button>
                    </form>
                </div>

                {jobs.data.length === 0 ? (
                    <EmptyState
                        icon={Briefcase}
                        title="No job postings found"
                        description="Check back soon or post an open position at your company to recruit fellow alumni."
                    />
                ) : (
                    <div className="space-y-4">
                        {jobs.data.map((job) => (
                            <div
                                key={job.ulid}
                                className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl border bg-card p-5 shadow-sm transition hover:shadow-md"
                            >
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="secondary" className="capitalize">
                                            {job.workplace_type.replace('_', ' ')}
                                        </Badge>
                                        <Badge variant="outline" className="capitalize">
                                            {job.employment_type.replace('_', ' ')}
                                        </Badge>
                                        {job.experience_level && (
                                            <Badge variant="outline" className="capitalize">
                                                {job.experience_level}
                                            </Badge>
                                        )}
                                    </div>

                                    <div>
                                        <h2 className="text-lg font-bold">
                                            <Link href={`/jobs/${job.ulid}`} className="hover:underline">
                                                {job.title}
                                            </Link>
                                        </h2>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            {job.company_name} {job.location && `• ${job.location}`}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                        {job.salary_range && (
                                            <span className="flex items-center gap-1 font-medium text-foreground">
                                                <DollarSign className="h-3.5 w-3.5 text-primary" />
                                                {job.salary_range}
                                            </span>
                                        )}
                                        {job.deadline_at && (
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3.5 w-3.5" />
                                                Deadline: {job.deadline_at}
                                            </span>
                                        )}
                                        {job.poster && (
                                            <span>
                                                Posted by {job.poster.full_name} {job.poster.batch && `(${job.poster.batch})`}
                                            </span>
                                        )}
                                    </div>
                                </div>

                                <div className="flex sm:flex-col items-end justify-between gap-2">
                                    <Button asChild size="sm">
                                        <Link href={`/jobs/${job.ulid}`}>
                                            View Details
                                            <ExternalLink className="ml-1.5 h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={jobs.meta} />
            </div>
        </MemberLayout>
    );
}
