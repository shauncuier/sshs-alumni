import { ArrowLeft, Building, MapPin, Phone, Mail, Globe, Tag, CheckCircle2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

type Business = {
    ulid: string;
    name: string;
    category: string;
    industry: string | null;
    tagline: string | null;
    description: string | null;
    address: string | null;
    city: string | null;
    district: string | null;
    country: string;
    phone: string | null;
    email: string | null;
    website: string | null;
    alumni_discount: string | null;
    owner?: {
        full_name: string;
        membership_no: string;
        batch?: string | null;
    } | null;
};

type Props = {
    business: Business;
};

export default function PublicBusinessShow({ business }: Props) {
    return (
        <div className="min-h-screen bg-background py-10 px-4 sm:px-6">
            <Head title={`${business.name} - SSHS Alumni Business Directory`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Button asChild variant="ghost" size="sm" className="-ml-2">
                    <Link href="/businesses">
                        <ArrowLeft className="mr-1.5 h-4 w-4" />
                        Back to Business Directory
                    </Link>
                </Button>

                <div className="overflow-hidden rounded-2xl border bg-card p-6 sm:p-10 shadow-sm">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2">
                                <Badge variant="secondary">{business.category}</Badge>
                                {business.industry && <Badge variant="outline">{business.industry}</Badge>}
                            </div>
                            <h1 className="text-2xl sm:text-4xl font-bold">{business.name}</h1>
                            {business.tagline && (
                                <p className="text-base text-muted-foreground italic">{business.tagline}</p>
                            )}
                        </div>

                        {business.owner && (
                            <div className="rounded-xl bg-muted/50 p-4 text-sm">
                                <p className="text-xs text-muted-foreground uppercase font-semibold">Alumni Founder</p>
                                <p className="font-medium mt-1">{business.owner.full_name}</p>
                                {business.owner.batch && (
                                    <p className="text-xs text-muted-foreground">Batch: {business.owner.batch}</p>
                                )}
                            </div>
                        )}
                    </div>

                    {business.alumni_discount && (
                        <div className="mt-6 flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4 text-primary">
                            <Tag className="h-5 w-5 shrink-0 mt-0.5" />
                            <div>
                                <h2 className="font-semibold text-sm">Special Alumni Offer</h2>
                                <p className="text-sm mt-0.5">{business.alumni_discount}</p>
                            </div>
                        </div>
                    )}

                    <div className="mt-8">
                        <h2 className="text-lg font-semibold mb-3">About the Company</h2>
                        <div className="text-muted-foreground leading-relaxed whitespace-pre-line text-sm sm:text-base">
                            {business.description ?? 'No detailed description provided.'}
                        </div>
                    </div>

                    <div className="mt-8 border-t pt-6">
                        <h2 className="text-lg font-semibold mb-4">Contact & Location</h2>
                        <div className="grid gap-3 sm:grid-cols-2 text-sm text-muted-foreground">
                            {business.address && (
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-4 w-4 shrink-0 text-primary" />
                                    <span>{business.address}, {business.city}, {business.country}</span>
                                </div>
                            )}
                            {business.phone && (
                                <div className="flex items-center gap-2">
                                    <Phone className="h-4 w-4 shrink-0 text-primary" />
                                    <span>{business.phone}</span>
                                </div>
                            )}
                            {business.email && (
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 shrink-0 text-primary" />
                                    <a href={`mailto:${business.email}`} className="hover:underline">{business.email}</a>
                                </div>
                            )}
                            {business.website && (
                                <div className="flex items-center gap-2">
                                    <Globe className="h-4 w-4 shrink-0 text-primary" />
                                    <a href={business.website} target="_blank" rel="noreferrer" className="hover:underline text-primary">
                                        {business.website}
                                    </a>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
