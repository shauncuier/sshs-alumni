import { Award, ExternalLink } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import MemberLayout from '@/layouts/member-layout';
import type { Paginated } from '@/types/member';

type Certificate = {
    ulid: string;
    certificate_no: string;
    recipient_name: string;
    title: string;
    description: string | null;
    type: string;
    issue_date: string;
    metadata: Record<string, any> | null;
    event?: { ulid: string; title: string } | null;
};

type Props = {
    certificates: Paginated<Certificate>;
};

export default function MemberCertificates({ certificates }: Props) {
    return (
        <MemberLayout title="My Digital Certificates">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">My Digital Certificates</h1>
                        <p className="text-muted-foreground text-sm">
                            Official verifiable certificates issued for your leadership, volunteer service, and participation.
                        </p>
                    </div>
                </div>

                {certificates.data.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No certificates issued yet"
                        description="Certificates awarded for your event participation, volunteer service, and achievements will appear here."
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {certificates.data.map((cert) => (
                            <div
                                key={cert.ulid}
                                className="flex flex-col justify-between rounded-xl border bg-card p-5 shadow-sm transition hover:shadow"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <Badge variant="outline" className="uppercase font-mono text-xs">
                                            {cert.certificate_no}
                                        </Badge>
                                        <Badge variant="secondary" className="capitalize text-xs">
                                            {cert.type}
                                        </Badge>
                                    </div>

                                    <h2 className="text-lg font-semibold leading-tight">{cert.title}</h2>
                                    {cert.description && (
                                        <p className="text-muted-foreground text-sm line-clamp-2">
                                            {cert.description}
                                        </p>
                                    )}
                                </div>

                                <div className="mt-5 pt-4 border-t flex items-center justify-between gap-2">
                                    <span className="text-xs text-muted-foreground">Issued: {cert.issue_date}</span>
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={`/my/certificates/${cert.ulid}`}>
                                            View Certificate
                                            <ExternalLink className="ml-1.5 h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={certificates.meta} />
            </div>
        </MemberLayout>
    );
}
