import { Award, CheckCircle2, XCircle, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Certificate = {
    ulid: string;
    certificate_no: string;
    recipient_name: string;
    title: string;
    description: string | null;
    type: string;
    issue_date: string;
    event?: { ulid: string; title: string } | null;
    member?: { full_name: string; membership_no: string; batch?: string } | null;
};

type Props = {
    certificate: Certificate | null;
    is_valid: boolean;
};

export default function VerifyCertificate({ certificate, is_valid }: Props) {
    return (
        <div className="min-h-screen bg-muted/30 flex flex-col justify-between py-12 px-4 sm:px-6 lg:px-8">
            <Head title="Verify Certificate - SSHS Alumni Association" />

            <div className="mx-auto w-full max-w-xl">
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2 font-bold text-lg text-primary">
                        SSHS Alumni Association
                    </Link>
                </div>

                {is_valid && certificate ? (
                    <div className="overflow-hidden rounded-2xl border bg-card p-8 shadow-xl text-center">
                        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                            <CheckCircle2 className="h-10 w-10" />
                        </div>

                        <Badge className="bg-emerald-600 hover:bg-emerald-700 text-white mb-3">
                            <ShieldCheck className="h-3.5 w-3.5 mr-1" /> Verified Authentic Certificate
                        </Badge>

                        <h1 className="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            {certificate.title}
                        </h1>

                        <p className="mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Recipient
                        </p>
                        <h2 className="text-2xl font-bold text-primary font-serif mt-1">
                            {certificate.recipient_name}
                        </h2>

                        {certificate.member?.batch && (
                            <p className="text-sm text-muted-foreground mt-1">
                                Batch: {certificate.member.batch} • Member ID: {certificate.member.membership_no}
                            </p>
                        )}

                        {certificate.description && (
                            <p className="mt-4 text-sm text-muted-foreground max-w-md mx-auto">
                                {certificate.description}
                            </p>
                        )}

                        <div className="mt-8 grid grid-cols-2 gap-4 border-t pt-6 text-left">
                            <div className="rounded-lg bg-muted/40 p-3">
                                <span className="text-xs text-muted-foreground block">Certificate Number</span>
                                <span className="font-mono text-sm font-semibold">{certificate.certificate_no}</span>
                            </div>
                            <div className="rounded-lg bg-muted/40 p-3">
                                <span className="text-xs text-muted-foreground block">Date of Issuance</span>
                                <span className="text-sm font-semibold">{certificate.issue_date}</span>
                            </div>
                        </div>

                        <div className="mt-8">
                            <Button asChild variant="outline" className="w-full">
                                <Link href="/">Return to Home</Link>
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-2xl border bg-card p-8 shadow-xl text-center">
                        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                            <XCircle className="h-10 w-10" />
                        </div>

                        <h1 className="text-2xl font-bold text-foreground">Certificate Not Found or Invalid</h1>
                        <p className="mt-3 text-sm text-muted-foreground">
                            We could not verify the authenticity of this certificate record. Please ensure you scanned the authentic QR code or entered the correct certificate URL.
                        </p>

                        <div className="mt-8">
                            <Button asChild variant="default" className="w-full">
                                <Link href="/">Return to Home</Link>
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            <footer className="text-center text-xs text-muted-foreground mt-12">
                © {new Date().getFullYear()} SSHS Alumni Association. All rights reserved.
            </footer>
        </div>
    );
}
