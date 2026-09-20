import { Award, CheckCircle2, QrCode, ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import MemberLayout from '@/layouts/member-layout';

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
    certificate: Certificate;
    verify_url: string;
};

export default function MemberCertificateShow({ certificate, verify_url }: Props) {
    return (
        <MemberLayout title={certificate.title}>
            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <Button asChild variant="ghost" size="sm" className="mb-3 -ml-2">
                        <Link href="/my/certificates">
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Back to Certificates
                        </Link>
                    </Button>
                </div>

                <div className="relative overflow-hidden rounded-2xl border-4 border-amber-500/20 bg-card p-8 sm:p-12 shadow-lg text-center">
                    <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">
                        <Award className="h-8 w-8" />
                    </div>

                    <p className="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                        SSHS Alumni Association
                    </p>
                    <h1 className="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
                        {certificate.title}
                    </h1>

                    <p className="mt-6 text-sm text-muted-foreground">This is proudly presented to</p>
                    <h2 className="mt-2 text-2xl font-serif font-bold text-primary">
                        {certificate.recipient_name}
                    </h2>

                    {certificate.description && (
                        <p className="mx-auto mt-4 max-w-lg text-sm text-muted-foreground leading-relaxed">
                            {certificate.description}
                        </p>
                    )}

                    <div className="mt-8 pt-6 border-t flex flex-wrap items-center justify-between gap-4 text-xs text-muted-foreground">
                        <div>
                            <span className="block font-mono font-medium text-foreground">{certificate.certificate_no}</span>
                            <span>Certificate ID</span>
                        </div>
                        <div>
                            <span className="block font-medium text-foreground">{certificate.issue_date}</span>
                            <span>Issue Date</span>
                        </div>
                        <div className="flex items-center gap-1.5 text-emerald-600 font-medium">
                            <CheckCircle2 className="h-4 w-4" />
                            <span>Digitally Verified</span>
                        </div>
                    </div>

                    <div className="mt-8 rounded-lg bg-muted/50 p-4 text-left">
                        <div className="flex items-center gap-2 mb-2">
                            <QrCode className="h-4 w-4 text-primary" />
                            <span className="text-xs font-semibold">Public Verification Link</span>
                        </div>
                        <p className="text-xs text-muted-foreground break-all select-all font-mono">
                            {verify_url}
                        </p>
                    </div>
                </div>
            </div>
        </MemberLayout>
    );
}
