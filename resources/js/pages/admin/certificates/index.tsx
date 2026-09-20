import { router, useForm } from '@inertiajs/react';
import { Award, ExternalLink, Plus, Search, Trash2 } from 'lucide-react';
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
import { formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/types/member';

type Certificate = {
    id: number;
    ulid: string;
    certificate_no: string;
    recipient_name: string;
    title: string;
    type: string;
    issue_date: string;
    qr_token: string;
    member: {
        ulid: string;
        full_name: string;
        membership_no: string | null;
    } | null;
    event: {
        title: string;
    } | null;
    verify_url: string;
    created_at: string | null;
};

type Props = {
    certificates: Paginated<Certificate>;
    filters: Record<string, string | null>;
    events: { id: number; title: string }[];
    members: { id: number; full_name: string; membership_no: string | null }[];
};

export default function AdminCertificatesIndex({ certificates, filters, events, members }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        recipient_name: '',
        title: '',
        description: '',
        type: 'Event Participation',
        issue_date: new Date().toISOString().split('T')[0],
        member_id: '',
        event_id: '',
    });

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/certificates', { q: q || undefined }, { preserveState: true });
    };

    const handleMemberSelect = (memberIdStr: string) => {
        setData('member_id', memberIdStr);
        if (memberIdStr) {
            const found = members.find((m) => String(m.id) === memberIdStr);
            if (found) {
                setData((prev) => ({ ...prev, member_id: memberIdStr, recipient_name: found.full_name }));
            }
        }
    };

    const handleIssue = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/certificates', {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const handleRevoke = (ulid: string) => {
        if (confirm('Are you sure you want to revoke this certificate?')) {
            router.delete(`/admin/certificates/${ulid}`);
        }
    };

    return (
        <AdminLayout title="Digital Certificates">
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Digital Verifiable Certificates
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Issue and manage cryptographically secured certificates of attendance, volunteering, and recognition.
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button className="rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold">
                                <Plus className="size-4 mr-1.5" />
                                Issue Certificate
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-lg">
                            <form onSubmit={handleIssue} className="space-y-4">
                                <DialogHeader>
                                    <DialogTitle>Issue Digital Certificate</DialogTitle>
                                    <DialogDescription>
                                        Create a permanent verifiable certificate for an alumnus, student, or guest.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="space-y-3">
                                    <div>
                                        <Label htmlFor="member_id">Link to Member (Optional)</Label>
                                        <select
                                            id="member_id"
                                            value={data.member_id}
                                            onChange={(e) => handleMemberSelect(e.target.value)}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <option value="">-- Guest / Non-member --</option>
                                            {members.map((m) => (
                                                <option key={m.id} value={m.id}>
                                                    {m.full_name} ({m.membership_no ?? 'Pending No'})
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <Label htmlFor="recipient_name">Recipient Full Name</Label>
                                        <Input
                                            id="recipient_name"
                                            value={data.recipient_name}
                                            onChange={(e) => setData('recipient_name', e.target.value)}
                                            placeholder="Recipient's legal or official name"
                                            required
                                        />
                                        {errors.recipient_name && <p className="text-xs text-destructive mt-1">{errors.recipient_name}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="title">Certificate Title</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            placeholder="e.g. Golden Jubilee Volunteer Excellence"
                                            required
                                        />
                                        {errors.title && <p className="text-xs text-destructive mt-1">{errors.title}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label htmlFor="type">Type / Category</Label>
                                            <select
                                                id="type"
                                                value={data.type}
                                                onChange={(e) => setData('type', e.target.value)}
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            >
                                                <option value="Event Participation">Event Participation</option>
                                                <option value="Volunteer Appreciation">Volunteer Appreciation</option>
                                                <option value="Lifetime Honor">Lifetime Honor</option>
                                                <option value="Executive Committee">Executive Committee</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label htmlFor="issue_date">Date of Issue</Label>
                                            <Input
                                                id="issue_date"
                                                type="date"
                                                value={data.issue_date}
                                                onChange={(e) => setData('issue_date', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="event_id">Associated Event (Optional)</Label>
                                        <select
                                            id="event_id"
                                            value={data.event_id}
                                            onChange={(e) => setData('event_id', e.target.value)}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <option value="">-- No specific event --</option>
                                            {events.map((ev) => (
                                                <option key={ev.id} value={ev.id}>
                                                    {ev.title}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <Label htmlFor="description">Citation / Description</Label>
                                        <Textarea
                                            id="description"
                                            rows={3}
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            placeholder="In recognition of outstanding dedication and service..."
                                        />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing} className="bg-purple-600 hover:bg-purple-700 text-white">
                                        {processing ? 'Issuing...' : 'Issue Certificate'}
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
                        placeholder="Search by recipient or certificate number..."
                        className="rounded-xl"
                    />
                    <Button type="submit" variant="secondary" className="rounded-xl">
                        <Search className="size-4" />
                    </Button>
                </form>

                {certificates.data.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No certificates issued yet"
                        description="Issue verifiable digital certificates for event delegates, volunteers, and donors."
                    />
                ) : (
                    <div className="grid gap-4">
                        {certificates.data.map((c) => (
                            <Card key={c.ulid} className="border-border/70">
                                <CardContent className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div className="space-y-1.5 flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h2 className="font-bold text-base text-foreground truncate">
                                                {c.title}
                                            </h2>
                                            <Badge variant="outline">{c.type}</Badge>
                                        </div>

                                        <p className="text-sm font-semibold text-purple-700 dark:text-purple-400">
                                            Recipient: {c.recipient_name}
                                        </p>

                                        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground pt-1">
                                            <span className="font-mono font-medium text-foreground">
                                                No: {c.certificate_no}
                                            </span>
                                            {c.event && <span>Event: {c.event.title}</span>}
                                            <span>Issued: {formatDate(c.issue_date)}</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="rounded-xl gap-1"
                                        >
                                            <a href={c.verify_url} target="_blank" rel="noopener noreferrer">
                                                <ExternalLink className="size-3.5" />
                                                Verify Page
                                            </a>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleRevoke(c.ulid)}
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

                <Pagination meta={certificates.meta} />
            </div>
        </AdminLayout>
    );
}
