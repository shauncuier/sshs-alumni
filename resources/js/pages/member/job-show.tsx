import { ArrowLeft, Building2, MapPin, Calendar, DollarSign, Send, CheckCircle2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import MemberLayout from '@/layouts/member-layout';

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
    requirements: string | null;
    application_url_or_email: string;
    deadline_at: string | null;
    poster?: {
        full_name: string;
        batch?: string | null;
    } | null;
};

type Props = {
    job: Job;
};

export default function MemberJobShow({ job }: Props) {
    const isEmail = job.application_url_or_email.includes('@');
    const applyHref = isEmail ? `mailto:${job.application_url_or_email}` : job.application_url_or_email;

    return (
        <MemberLayout title={`${job.title} at ${job.company_name}`}>
            <div className="mx-auto max-w-4xl space-y-6">
                <Button asChild variant="ghost" size="sm" className="-ml-2">
                    <Link href="/jobs">
                        <ArrowLeft className="mr-1.5 h-4 w-4" />
                        Back to Job Board
                    </Link>
                </Button>

                <div className="overflow-hidden rounded-2xl border bg-card p-6 sm:p-10 shadow-sm">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="secondary" className="capitalize">{job.workplace_type.replace('_', ' ')}</Badge>
                                <Badge variant="outline" className="capitalize">{job.employment_type.replace('_', ' ')}</Badge>
                                {job.experience_level && (
                                    <Badge variant="outline" className="capitalize">{job.experience_level}</Badge>
                                )}
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold">{job.title}</h1>
                            <p className="text-lg font-medium text-muted-foreground flex items-center gap-2">
                                <Building2 className="h-5 w-5 text-primary" />
                                {job.company_name} {job.location && `• ${job.location}`}
                            </p>
                        </div>

                        <Button asChild size="lg" className="w-full sm:w-auto">
                            <a href={applyHref} target="_blank" rel="noreferrer">
                                Apply Now <Send className="ml-2 h-4 w-4" />
                            </a>
                        </Button>
                    </div>

                    <div className="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4 border-y py-4 text-sm">
                        {job.salary_range && (
                            <div>
                                <span className="text-xs text-muted-foreground block">Salary Range</span>
                                <span className="font-semibold text-foreground">{job.salary_range}</span>
                            </div>
                        )}
                        {job.deadline_at && (
                            <div>
                                <span className="text-xs text-muted-foreground block">Application Deadline</span>
                                <span className="font-semibold text-foreground">{job.deadline_at}</span>
                            </div>
                        )}
                        {job.poster && (
                            <div>
                                <span className="text-xs text-muted-foreground block">Posted By</span>
                                <span className="font-semibold text-foreground">
                                    {job.poster.full_name} {job.poster.batch && `(${job.poster.batch})`}
                                </span>
                            </div>
                        )}
                    </div>

                    <div className="mt-8 space-y-6">
                        <div>
                            <h2 className="text-lg font-semibold mb-3">Job Description</h2>
                            <div className="text-muted-foreground leading-relaxed whitespace-pre-line text-sm sm:text-base">
                                {job.description}
                            </div>
                        </div>

                        {job.requirements && (
                            <div>
                                <h2 className="text-lg font-semibold mb-3">Requirements & Qualifications</h2>
                                <div className="text-muted-foreground leading-relaxed whitespace-pre-line text-sm sm:text-base">
                                    {job.requirements}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="mt-10 rounded-xl bg-muted/40 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h3 className="font-semibold">Interested in this opportunity?</h3>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Reach out directly through the employer's application link or contact email.
                            </p>
                        </div>
                        <Button asChild>
                            <a href={applyHref} target="_blank" rel="noreferrer">
                                Apply Now ({job.application_url_or_email})
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </MemberLayout>
    );
}
