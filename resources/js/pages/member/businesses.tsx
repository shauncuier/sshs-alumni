import { Briefcase, Plus, MapPin, Phone, Mail, Globe, Tag } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import MemberLayout from '@/layouts/member-layout';
import type { Paginated } from '@/types/member';

type Business = {
    ulid: string;
    name: string;
    category: string;
    industry: string | null;
    tagline: string | null;
    description: string | null;
    city: string | null;
    district: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    alumni_discount: string | null;
    status: string;
    status_label: string;
};

type Props = {
    businesses: Paginated<Business>;
};

export default function MemberBusinesses({ businesses }: Props) {
    return (
        <MemberLayout title="My Business Listings">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">My Business Listings</h1>
                        <p className="text-muted-foreground text-sm">
                            Promote your company, services, and special alumni offers to the SSHS network.
                        </p>
                    </div>
                </div>

                {businesses.data.length === 0 ? (
                    <EmptyState
                        icon={Briefcase}
                        title="No business listings added yet"
                        description="Add your enterprise or professional practice to connect with alumni clients and partners."
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {businesses.data.map((biz) => (
                            <div key={biz.ulid} className="flex flex-col justify-between rounded-xl border bg-card p-5 shadow-sm">
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <Badge variant="secondary">{biz.category}</Badge>
                                        <Badge variant={biz.status === 'published' ? 'default' : 'outline'}>
                                            {biz.status_label}
                                        </Badge>
                                    </div>

                                    <div>
                                        <h2 className="text-lg font-semibold">{biz.name}</h2>
                                        {biz.tagline && <p className="text-sm text-muted-foreground italic">{biz.tagline}</p>}
                                    </div>

                                    {biz.description && (
                                        <p className="text-sm text-muted-foreground line-clamp-3 leading-relaxed">
                                            {biz.description}
                                        </p>
                                    )}

                                    {biz.alumni_discount && (
                                        <div className="flex items-center gap-1.5 text-xs text-primary font-medium bg-primary/5 p-2 rounded">
                                            <Tag className="h-3.5 w-3.5" />
                                            <span>Offer: {biz.alumni_discount}</span>
                                        </div>
                                    )}

                                    <div className="space-y-1 text-xs text-muted-foreground pt-2">
                                        {biz.city && (
                                            <div className="flex items-center gap-1.5">
                                                <MapPin className="h-3.5 w-3.5" />
                                                <span>{biz.city}, {biz.district ?? 'Bangladesh'}</span>
                                            </div>
                                        )}
                                        {biz.phone && (
                                            <div className="flex items-center gap-1.5">
                                                <Phone className="h-3.5 w-3.5" />
                                                <span>{biz.phone}</span>
                                            </div>
                                        )}
                                        {biz.website && (
                                            <div className="flex items-center gap-1.5">
                                                <Globe className="h-3.5 w-3.5" />
                                                <a href={biz.website} target="_blank" rel="noreferrer" className="hover:underline truncate">
                                                    {biz.website}
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={businesses.meta} />
            </div>
        </MemberLayout>
    );
}
