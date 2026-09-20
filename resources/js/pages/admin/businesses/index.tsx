import { router } from '@inertiajs/react';
import { Building, CheckCircle, ExternalLink, Search, Trash2, XCircle } from 'lucide-react';
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

type Business = {
    id: number;
    ulid: string;
    name: string;
    category: string;
    city: string | null;
    phone: string | null;
    email: string | null;
    alumni_discount: string | null;
    status: string;
    member: {
        ulid: string;
        full_name: string;
        membership_no: string | null;
    } | null;
    created_at: string | null;
};

type Props = {
    businesses: Paginated<Business>;
    filters: Record<string, string | null>;
    statuses: string[];
};

export default function AdminBusinessesIndex({ businesses, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/businesses', { q: q || undefined }, { preserveState: true });
    };

    const handleTogglePublish = (ulid: string) => {
        router.put(`/admin/businesses/${ulid}/publish`, {}, { preserveScroll: true });
    };

    const handleDelete = (ulid: string) => {
        if (confirm('Are you sure you want to delete this business listing?')) {
            router.delete(`/admin/businesses/${ulid}`);
        }
    };

    return (
        <AdminLayout title="Alumni Businesses">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Alumni Business Directory
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Moderate, verify, and monitor enterprise listings submitted by verified alumni.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSearch} className="flex gap-2 max-w-md">
                    <Input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search by business name..."
                        className="rounded-xl"
                    />
                    <Button type="submit" variant="secondary" className="rounded-xl">
                        <Search className="size-4" />
                    </Button>
                </form>

                {businesses.data.length === 0 ? (
                    <EmptyState
                        icon={Building}
                        title="No businesses found"
                        description="No alumni businesses match the active filters."
                    />
                ) : (
                    <div className="grid gap-4">
                        {businesses.data.map((b) => (
                            <Card key={b.ulid} className="border-border/70">
                                <CardContent className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div className="space-y-1.5 flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h2 className="font-bold text-base text-foreground truncate">
                                                {b.name}
                                            </h2>
                                            <Badge variant="outline">{b.category}</Badge>
                                            <Badge variant={b.status === 'published' ? 'default' : 'secondary'}>
                                                {b.status}
                                            </Badge>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                            {b.member && (
                                                <span>
                                                    Owner: <strong className="text-foreground">{b.member.full_name}</strong>
                                                    {b.member.membership_no && ` (${b.member.membership_no})`}
                                                </span>
                                            )}
                                            {b.city && <span>Location: {b.city}</span>}
                                            {b.phone && <span>Phone: {b.phone}</span>}
                                            {b.alumni_discount && (
                                                <span className="text-emerald-600 dark:text-emerald-400 font-semibold">
                                                    Discount: {b.alumni_discount}
                                                </span>
                                            )}
                                            {b.created_at && <span>Submitted: {formatDate(b.created_at)}</span>}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleTogglePublish(b.ulid)}
                                            className="rounded-xl gap-1"
                                        >
                                            {b.status === 'published' ? (
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
                                            asChild
                                            variant="ghost"
                                            size="sm"
                                            className="rounded-xl"
                                        >
                                            <a href={`/businesses/${b.ulid}`} target="_blank" rel="noopener noreferrer">
                                                <ExternalLink className="size-4" />
                                            </a>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleDelete(b.ulid)}
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

                <Pagination meta={businesses.meta} />
            </div>
        </AdminLayout>
    );
}
