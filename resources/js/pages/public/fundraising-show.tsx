import { ArrowLeft, Heart, Calendar, Target, Users, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { formatCurrency } from '@/lib/format';

type Donor = {
    donor_name: string;
    amount: number;
    received_at: string | null;
};

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
    recent_donors?: Donor[];
};

type Props = {
    campaign: Campaign;
};

export default function PublicFundraisingShow({ campaign }: Props) {
    return (
        <div className="min-h-screen bg-background py-10 px-4 sm:px-6">
            <Head title={`${campaign.title} - SSHS Alumni Campaign`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Button asChild variant="ghost" size="sm" className="-ml-2">
                    <Link href="/campaigns">
                        <ArrowLeft className="mr-1.5 h-4 w-4" />
                        Back to Campaigns
                    </Link>
                </Button>

                <div className="overflow-hidden rounded-2xl border bg-card p-6 sm:p-10 shadow-sm">
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">Official Campaign</Badge>
                            {campaign.days_left !== null && (
                                <Badge variant="outline">
                                    <Calendar className="mr-1 h-3 w-3" />
                                    {campaign.days_left > 0 ? `${campaign.days_left} days remaining` : 'Concluded'}
                                </Badge>
                            )}
                        </div>

                        <h1 className="text-2xl sm:text-4xl font-bold">{campaign.title}</h1>
                        {campaign.tagline && (
                            <p className="text-base sm:text-lg text-muted-foreground italic">{campaign.tagline}</p>
                        )}
                    </div>

                    <div className="my-8 rounded-xl bg-muted/40 p-6 space-y-4">
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center">
                            <div>
                                <span className="text-xs text-muted-foreground block">Total Raised</span>
                                <span className="text-2xl font-bold text-primary">{formatCurrency(campaign.raised_amount)}</span>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground block">Fundraising Goal</span>
                                <span className="text-2xl font-bold">{formatCurrency(campaign.goal_amount)}</span>
                            </div>
                            <div className="col-span-2 sm:col-span-1">
                                <span className="text-xs text-muted-foreground block">Funded</span>
                                <span className="text-2xl font-bold">{campaign.progress_percentage}%</span>
                            </div>
                        </div>

                        <div className="h-3 w-full overflow-hidden rounded-full bg-secondary">
                            <div
                                className="h-full bg-primary transition-all duration-300"
                                style={{ width: `${Math.min(100, campaign.progress_percentage)}%` }}
                            />
                        </div>

                        <div className="pt-2 text-center">
                            <Button asChild size="lg" className="w-full sm:w-auto px-10">
                                <Link href={`/donate?campaign=${encodeURIComponent(campaign.title)}`}>
                                    Contribute to this Campaign
                                    <Heart className="ml-2 h-4 w-4 fill-white" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div>
                            <h2 className="text-xl font-bold mb-3">About the Project</h2>
                            <div className="text-muted-foreground leading-relaxed whitespace-pre-line text-sm sm:text-base">
                                {campaign.description}
                            </div>
                        </div>

                        {campaign.recent_donors && campaign.recent_donors.length > 0 && (
                            <div className="border-t pt-6">
                                <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                                    <Users className="h-5 w-5 text-primary" />
                                    Recent Backers & Contributors
                                </h2>
                                <div className="divide-y rounded-xl border">
                                    {campaign.recent_donors.map((donor, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-3.5 text-sm">
                                            <span className="font-medium">{donor.donor_name}</span>
                                            <div className="flex items-center gap-3">
                                                <span className="font-semibold text-primary">{formatCurrency(donor.amount)}</span>
                                                {donor.received_at && (
                                                    <span className="text-xs text-muted-foreground">{donor.received_at}</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
