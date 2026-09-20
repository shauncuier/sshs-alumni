import { Heart, Target, Calendar, ArrowRight, ShieldCheck } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { formatCurrency } from '@/lib/format';
import type { Paginated } from '@/types/member';

type Campaign = {
    ulid: string;
    slug: string;
    title: string;
    tagline: string | null;
    description: string;
    goal_amount: number;
    raised_amount: number;
    progress_percentage: number;
    is_goal_reached: boolean;
    days_left: number | null;
    cover_image_url: string | null;
    is_featured: boolean;
};

type Props = {
    campaigns: Paginated<Campaign>;
};

export default function PublicFundraising({ campaigns }: Props) {
    return (
        <div className="min-h-screen bg-background">
            <Head title="Fundraising Campaigns - SSHS Alumni Giving" />

            <header className="border-b bg-muted/30 py-12 px-4 text-center">
                <div className="mx-auto max-w-4xl space-y-4">
                    <Badge variant="outline" className="text-primary gap-1">
                        <Heart className="h-3.5 w-3.5 fill-primary" /> Giving & Impact
                    </Badge>
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Fundraising Campaigns
                    </h1>
                    <p className="mx-auto max-w-2xl text-muted-foreground text-sm sm:text-base">
                        Support scholarships, heritage restoration, laboratories, and student welfare initiatives at Siddheswari Boys' Higher Secondary School.
                    </p>
                </div>
            </header>

            <main className="mx-auto max-w-6xl py-10 px-4 sm:px-6">
                {campaigns.data.length === 0 ? (
                    <EmptyState
                        icon={Heart}
                        title="No active fundraising campaigns"
                        description="Check back soon for upcoming initiatives and projects."
                    />
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {campaigns.data.map((c) => (
                            <div
                                key={c.ulid}
                                className="flex flex-col justify-between overflow-hidden rounded-2xl border bg-card shadow-sm transition hover:shadow-md"
                            >
                                <div className="p-6 space-y-4">
                                    <div className="flex items-center justify-between gap-2">
                                        {c.is_featured ? (
                                            <Badge className="bg-amber-600">Featured Initiative</Badge>
                                        ) : (
                                            <Badge variant="secondary">Active Campaign</Badge>
                                        )}
                                        {c.days_left !== null && (
                                            <span className="text-xs text-muted-foreground flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                {c.days_left > 0 ? `${c.days_left} days left` : 'Completed'}
                                            </span>
                                        )}
                                    </div>

                                    <div>
                                        <h2 className="text-xl font-bold leading-snug">
                                            <Link href={`/campaigns/${c.slug}`} className="hover:underline">
                                                {c.title}
                                            </Link>
                                        </h2>
                                        {c.tagline && (
                                            <p className="text-xs text-muted-foreground mt-1 italic">{c.tagline}</p>
                                        )}
                                    </div>

                                    <p className="text-sm text-muted-foreground line-clamp-3">
                                        {c.description}
                                    </p>

                                    <div className="space-y-2 pt-2">
                                        <div className="flex justify-between text-xs font-semibold">
                                            <span>Raised: {formatCurrency(c.raised_amount)}</span>
                                            <span className="text-muted-foreground">
                                                Goal: {formatCurrency(c.goal_amount)}
                                            </span>
                                        </div>
                                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-secondary">
                                            <div
                                                className="h-full bg-primary transition-all duration-300"
                                                style={{ width: `${Math.min(100, c.progress_percentage)}%` }}
                                            />
                                        </div>
                                        <div className="text-right text-xs font-semibold text-primary">
                                            {c.progress_percentage}% funded
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t bg-muted/20 p-4 text-center">
                                    <Button asChild className="w-full">
                                        <Link href={`/campaigns/${c.slug}`}>
                                            Donate & View Campaign
                                            <ArrowRight className="ml-1.5 h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-8">
                    <Pagination meta={campaigns.meta} />
                </div>
            </main>
        </div>
    );
}
