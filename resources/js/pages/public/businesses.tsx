import { Briefcase, MapPin, Search, Tag, ExternalLink } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
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
    website: string | null;
    alumni_discount: string | null;
    owner?: {
        full_name: string;
        batch?: string | null;
    } | null;
};

type Props = {
    businesses: Paginated<Business>;
    filters: {
        q?: string | null;
        category?: string | null;
        city?: string | null;
    };
    categories: string[];
};

export default function PublicBusinesses({ businesses, filters, categories }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/businesses', { ...filters, q: search }, { preserveState: true });
    };

    return (
        <div className="min-h-screen bg-background">
            <Head title="Alumni Business Directory - SSHS Alumni" />

            <header className="border-b bg-muted/40 py-12 px-4 text-center">
                <div className="mx-auto max-w-4xl space-y-4">
                    <Badge variant="outline" className="text-primary">
                        SSHS Alumni Ecosystem
                    </Badge>
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Alumni Business Directory
                    </h1>
                    <p className="mx-auto max-w-2xl text-muted-foreground text-sm sm:text-base">
                        Discover, support, and collaborate with enterprises founded and run by SSHS alumni around the world.
                    </p>

                    <form onSubmit={handleSearch} className="mx-auto mt-6 flex max-w-md gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search by name, industry, keyword..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit">Search</Button>
                    </form>
                </div>
            </header>

            <main className="mx-auto max-w-6xl py-10 px-4 sm:px-6">
                {businesses.data.length === 0 ? (
                    <EmptyState
                        icon={Briefcase}
                        title="No businesses found"
                        description="Try clearing your search filters to see all alumni enterprises."
                    />
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {businesses.data.map((biz) => (
                            <div
                                key={biz.ulid}
                                className="flex flex-col justify-between rounded-xl border bg-card p-6 shadow-sm transition hover:shadow-md"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <Badge variant="secondary">{biz.category}</Badge>
                                        {biz.city && (
                                            <span className="text-xs text-muted-foreground flex items-center gap-1">
                                                <MapPin className="h-3 w-3" />
                                                {biz.city}
                                            </span>
                                        )}
                                    </div>

                                    <h2 className="text-xl font-bold">{biz.name}</h2>
                                    {biz.tagline && (
                                        <p className="text-xs italic text-muted-foreground">{biz.tagline}</p>
                                    )}

                                    {biz.description && (
                                        <p className="text-sm text-muted-foreground line-clamp-3">
                                            {biz.description}
                                        </p>
                                    )}

                                    {biz.alumni_discount && (
                                        <div className="flex items-center gap-1.5 text-xs text-primary font-medium bg-primary/5 p-2 rounded">
                                            <Tag className="h-3.5 w-3.5" />
                                            <span>{biz.alumni_discount}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="mt-6 border-t pt-4 flex items-center justify-between">
                                    {biz.owner && (
                                        <span className="text-xs text-muted-foreground">
                                            By {biz.owner.full_name} {biz.owner.batch && `(${biz.owner.batch})`}
                                        </span>
                                    )}
                                    <Button asChild size="sm" variant="ghost">
                                        <Link href={`/businesses/${biz.ulid}`}>
                                            Details <ExternalLink className="ml-1 h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-8">
                    <Pagination meta={businesses.meta} />
                </div>
            </main>
        </div>
    );
}
