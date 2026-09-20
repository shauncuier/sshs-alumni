import { router, useForm } from '@inertiajs/react';
import { Gift, Plus, Search, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency, formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/types/member';

type Campaign = {
    id: number;
    ulid: string;
    slug: string;
    title: string;
    tagline: string | null;
    description: string;
    goal_amount: number;
    raised_amount: number;
    progress_percentage: number;
    donations_count: number;
    is_featured: boolean;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    published_at: string | null;
};

type Props = {
    campaigns: Paginated<Campaign>;
    filters: Record<string, string | null>;
    statuses: string[];
};

export default function AdminFundraisingIndex({ campaigns, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        tagline: '',
        description: '',
        goal_amount: '',
        starts_at: '',
        ends_at: '',
        is_featured: false,
        status: 'published',
    });

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/fundraising', { q: q || undefined }, { preserveState: true });
    };

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/fundraising', {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this fundraising campaign?')) {
            router.delete(`/admin/fundraising/${id}`);
        }
    };

    return (
        <AdminLayout title="Fundraising Campaigns">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Fundraising Campaigns
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage institution development, scholarship, and Golden Jubilee fundraising drives.
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button className="rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold">
                                <Plus className="size-4 mr-1.5" />
                                New Campaign
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-lg">
                            <form onSubmit={handleCreate} className="space-y-4">
                                <DialogHeader>
                                    <DialogTitle>Create Fundraising Campaign</DialogTitle>
                                    <DialogDescription>
                                        Launch a targeted development fund or event campaign for alumni contributions.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="space-y-3">
                                    <div>
                                        <Label htmlFor="title">Campaign Title</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            placeholder="e.g. Science Lab Modernization 2026"
                                            required
                                        />
                                        {errors.title && <p className="text-xs text-destructive mt-1">{errors.title}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="tagline">Tagline / Short Hook</Label>
                                        <Input
                                            id="tagline"
                                            value={data.tagline}
                                            onChange={(e) => setData('tagline', e.target.value)}
                                            placeholder="Equipping the next generation of students"
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="goal_amount">Target Goal Amount (BDT)</Label>
                                        <Input
                                            id="goal_amount"
                                            type="number"
                                            step="100"
                                            value={data.goal_amount}
                                            onChange={(e) => setData('goal_amount', e.target.value)}
                                            placeholder="500000"
                                            required
                                        />
                                        {errors.goal_amount && <p className="text-xs text-destructive mt-1">{errors.goal_amount}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="description">Detailed Description & Impact Story</Label>
                                        <Textarea
                                            id="description"
                                            rows={4}
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            placeholder="Explain why this project matters and how the funds will be utilized..."
                                            required
                                        />
                                        {errors.description && <p className="text-xs text-destructive mt-1">{errors.description}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label htmlFor="starts_at">Start Date</Label>
                                            <Input
                                                id="starts_at"
                                                type="date"
                                                value={data.starts_at}
                                                onChange={(e) => setData('starts_at', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="ends_at">Target Deadline</Label>
                                            <Input
                                                id="ends_at"
                                                type="date"
                                                value={data.ends_at}
                                                onChange={(e) => setData('ends_at', e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 pt-1">
                                        <input
                                            type="checkbox"
                                            id="is_featured"
                                            checked={data.is_featured}
                                            onChange={(e) => setData('is_featured', e.target.checked)}
                                            className="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <Label htmlFor="is_featured" className="text-sm cursor-pointer">
                                            Feature prominently on homepage and campaign listings
                                        </Label>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing} className="bg-teal-600 hover:bg-teal-700 text-white">
                                        {processing ? 'Creating...' : 'Create Campaign'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <form onSubmit={handleSearch} className="flex gap-2 max-w-md">
                    <Input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search campaigns..."
                        className="rounded-xl"
                    />
                    <Button type="submit" variant="secondary" className="rounded-xl">
                        <Search className="size-4" />
                    </Button>
                </form>

                {campaigns.data.length === 0 ? (
                    <EmptyState
                        icon={Gift}
                        title="No campaigns found"
                        description="Create your first fundraising campaign to mobilize alumni donations."
                    />
                ) : (
                    <div className="grid gap-4">
                        {campaigns.data.map((c) => (
                            <Card key={c.id} className="overflow-hidden border-border/70 hover:shadow-md transition-shadow">
                                <CardContent className="p-5">
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div className="space-y-1.5 flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <h2 className="font-bold text-lg text-foreground tracking-tight truncate">
                                                    {c.title}
                                                </h2>
                                                <Badge variant={c.status === 'published' ? 'default' : 'secondary'}>
                                                    {c.status}
                                                </Badge>
                                                {c.is_featured && (
                                                    <Badge className="bg-amber-500 text-slate-950 font-bold">
                                                        Featured
                                                    </Badge>
                                                )}
                                            </div>
                                            {c.tagline && <p className="text-sm text-muted-foreground line-clamp-1">{c.tagline}</p>}

                                            <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground pt-1">
                                                <span>Goal: <strong className="text-foreground">{formatCurrency(c.goal_amount)}</strong></span>
                                                <span>Raised: <strong className="text-teal-600 font-semibold">{formatCurrency(c.raised_amount)}</strong></span>
                                                <span>Donations: <strong className="text-foreground">{c.donations_count}</strong></span>
                                                {c.ends_at && <span>Deadline: {formatDate(c.ends_at)}</span>}
                                            </div>

                                            {/* Progress Bar */}
                                            <div className="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden mt-3 max-w-md">
                                                <div
                                                    className="bg-teal-600 h-full rounded-full transition-all"
                                                    style={{ width: `${c.progress_percentage}%` }}
                                                />
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 self-end sm:self-center">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="rounded-xl"
                                            >
                                                <a href={`/campaigns/${c.slug}`} target="_blank" rel="noopener noreferrer">
                                                    Public Page
                                                </a>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleDelete(c.id)}
                                                className="text-destructive hover:bg-destructive/10 rounded-xl"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination meta={campaigns.meta} />
            </div>
        </AdminLayout>
    );
}
