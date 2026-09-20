import { GraduationCap, Briefcase, MessageSquare, Check, X, Search, Sparkles, Send } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import MemberLayout from '@/layouts/member-layout';
import type { Paginated } from '@/types/member';

type Mentor = {
    ulid: string;
    member_id: number;
    title: string;
    company_or_institution: string;
    expertise: string[];
    bio: string;
    years_of_experience: number;
    max_mentees: number;
    is_available: boolean;
    member?: {
        full_name: string;
        batch?: string | null;
        membership_no: string;
    } | null;
};

type MentorshipRequest = {
    ulid: string;
    topic: string;
    message: string;
    status: string;
    response_note: string | null;
    created_at: string;
    mentor?: { full_name: string; batch?: string | null } | null;
    mentee?: { full_name: string; batch?: string | null; email?: string | null } | null;
};

type Props = {
    mentors: Paginated<Mentor>;
    my_profile: Mentor | null;
    incoming_requests: MentorshipRequest[];
    outgoing_requests: MentorshipRequest[];
    filters: {
        q?: string | null;
        topic?: string | null;
    };
};

export default function MemberMentorship({
    mentors,
    my_profile,
    incoming_requests,
    outgoing_requests,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [selectedMentor, setSelectedMentor] = useState<Mentor | null>(null);
    const [requestTopic, setRequestTopic] = useState('');
    const [requestMessage, setRequestMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [activeTab, setActiveTab] = useState<'browse' | 'requests' | 'profile'>('browse');

    // Profile form state
    const [profileTitle, setProfileTitle] = useState(my_profile?.title ?? '');
    const [profileCompany, setProfileCompany] = useState(my_profile?.company_or_institution ?? '');
    const [profileExpertise, setProfileExpertise] = useState(my_profile?.expertise?.join(', ') ?? '');
    const [profileBio, setProfileBio] = useState(my_profile?.bio ?? '');
    const [profileExp, setProfileExp] = useState(my_profile?.years_of_experience ?? 5);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/mentorship', { ...filters, q: search }, { preserveState: true });
    };

    const submitRequest = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMentor) return;
        setIsSubmitting(true);
        router.post('/mentorship/request', {
            mentor_id: selectedMentor.member_id,
            topic: requestTopic,
            message: requestMessage,
        }, {
            onSuccess: () => {
                setSelectedMentor(null);
                setRequestTopic('');
                setRequestMessage('');
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false),
        });
    };

    const handleRespond = (requestId: string, status: string) => {
        router.patch(`/mentorship/requests/${requestId}`, { status });
    };

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/mentorship/profile', {
            title: profileTitle,
            company_or_institution: profileCompany,
            expertise: profileExpertise.split(',').map(s => s.trim()).filter(Boolean),
            bio: profileBio,
            years_of_experience: Number(profileExp),
            max_mentees: 3,
            is_available: true,
        });
    };

    return (
        <MemberLayout title="Alumni Mentorship Network">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">Alumni Mentorship Network</h1>
                    <p className="text-muted-foreground text-sm">
                        Connect with senior alumni for career advice, higher study guidance, and professional development.
                    </p>
                </div>

                <div className="flex border-b border-border space-x-6">
                    <button
                        type="button"
                        onClick={() => setActiveTab('browse')}
                        className={`pb-3 text-sm font-semibold border-b-2 transition ${activeTab === 'browse' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                    >
                        Find Mentors ({mentors.meta?.total ?? mentors.data.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('requests')}
                        className={`pb-3 text-sm font-semibold border-b-2 transition ${activeTab === 'requests' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                    >
                        Requests {incoming_requests.length > 0 && `(${incoming_requests.length})`}
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('profile')}
                        className={`pb-3 text-sm font-semibold border-b-2 transition ${activeTab === 'profile' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                    >
                        {my_profile ? 'My Mentor Profile' : 'Become a Mentor'}
                    </button>
                </div>

                {activeTab === 'browse' && (
                    <div className="space-y-6">
                        <form onSubmit={handleSearch} className="flex max-w-md gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="search"
                                    placeholder="Search by name, expertise, company..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>

                        {mentors.data.length === 0 ? (
                            <EmptyState
                                icon={GraduationCap}
                                title="No mentors found"
                                description="Try adjusting your keywords or check back soon as more alumni register."
                            />
                        ) : (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {mentors.data.map((m) => (
                                    <div key={m.ulid} className="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-sm">
                                        <div className="space-y-3">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <h2 className="font-bold text-lg">{m.member?.full_name}</h2>
                                                    {m.member?.batch && (
                                                        <p className="text-xs text-muted-foreground">Batch: {m.member.batch}</p>
                                                    )}
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    {m.years_of_experience}+ yrs exp
                                                </Badge>
                                            </div>

                                            <div>
                                                <p className="text-sm font-semibold text-primary">{m.title}</p>
                                                <p className="text-xs text-muted-foreground">{m.company_or_institution}</p>
                                            </div>

                                            <div className="flex flex-wrap gap-1 pt-1">
                                                {m.expertise.map((exp, i) => (
                                                    <Badge key={i} variant="secondary" className="text-xs font-normal">
                                                        {exp}
                                                    </Badge>
                                                ))}
                                            </div>

                                            <p className="text-xs text-muted-foreground line-clamp-3 leading-relaxed">
                                                {m.bio}
                                            </p>
                                        </div>

                                        <div className="mt-6 border-t pt-4">
                                            <Button
                                                onClick={() => setSelectedMentor(m)}
                                                className="w-full"
                                                size="sm"
                                            >
                                                Request Mentorship
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        <Pagination meta={mentors.meta} />
                    </div>
                )}

                {activeTab === 'requests' && (
                    <div className="space-y-6">
                        <div className="space-y-4">
                            <h2 className="text-lg font-semibold">Incoming Requests for You</h2>
                            {incoming_requests.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No incoming requests right now.</p>
                            ) : (
                                <div className="divide-y rounded-xl border bg-card">
                                    {incoming_requests.map((r) => (
                                        <div key={r.ulid} className="p-4 flex flex-col sm:flex-row justify-between gap-4">
                                            <div>
                                                <p className="font-medium text-sm">{r.mentee?.full_name} ({r.mentee?.batch})</p>
                                                <p className="text-xs font-semibold text-primary mt-0.5">Topic: {r.topic}</p>
                                                <p className="text-sm text-muted-foreground mt-2 whitespace-pre-line">{r.message}</p>
                                            </div>
                                            <div className="flex sm:flex-col items-end justify-between gap-2">
                                                <Badge variant={r.status === 'accepted' ? 'default' : 'secondary'} className="capitalize">
                                                    {r.status}
                                                </Badge>
                                                {r.status === 'pending' && (
                                                    <div className="flex gap-2">
                                                        <Button size="sm" onClick={() => handleRespond(r.ulid, 'accepted')}>
                                                            Accept
                                                        </Button>
                                                        <Button size="sm" variant="outline" onClick={() => handleRespond(r.ulid, 'rejected')}>
                                                            Decline
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="space-y-4 border-t pt-6">
                            <h2 className="text-lg font-semibold">Requests Sent by You</h2>
                            {outgoing_requests.length === 0 ? (
                                <p className="text-sm text-muted-foreground">You haven't requested mentorship yet.</p>
                            ) : (
                                <div className="divide-y rounded-xl border bg-card">
                                    {outgoing_requests.map((r) => (
                                        <div key={r.ulid} className="p-4 flex items-center justify-between">
                                            <div>
                                                <p className="font-medium text-sm">To: {r.mentor?.full_name}</p>
                                                <p className="text-xs text-muted-foreground">Topic: {r.topic}</p>
                                            </div>
                                            <Badge variant={r.status === 'accepted' ? 'default' : 'secondary'} className="capitalize">
                                                {r.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'profile' && (
                    <div className="space-y-6">
                        <div className="max-w-xl rounded-2xl border bg-card p-6 shadow-sm">
                            <h2 className="text-lg font-semibold mb-1">
                                {my_profile ? 'Update Mentor Profile' : 'Register as an Alumnus Mentor'}
                            </h2>
                            <p className="text-xs text-muted-foreground mb-6">
                                Give back to Siddheswari alumni by sharing your guidance and expertise.
                            </p>

                            <form onSubmit={submitProfile} className="space-y-4">
                                <div>
                                    <label className="text-xs font-medium">Job Title / Designation</label>
                                    <Input value={profileTitle} onChange={e => setProfileTitle(e.target.value)} required />
                                </div>
                                <div>
                                    <label className="text-xs font-medium">Company or Institution</label>
                                    <Input value={profileCompany} onChange={e => setProfileCompany(e.target.value)} required />
                                </div>
                                <div>
                                    <label className="text-xs font-medium">Areas of Expertise (comma-separated)</label>
                                    <Input
                                        placeholder="e.g. Higher Study in USA, Software Engineering, Civil Service"
                                        value={profileExpertise}
                                        onChange={e => setProfileExpertise(e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium">Years of Experience</label>
                                    <Input type="number" min="0" value={profileExp} onChange={e => setProfileExp(Number(e.target.value))} required />
                                </div>
                                <div>
                                    <label className="text-xs font-medium">Mentorship Bio & Expectations</label>
                                    <textarea
                                        className="w-full rounded-md border bg-background p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                        rows={4}
                                        value={profileBio}
                                        onChange={e => setProfileBio(e.target.value)}
                                        required
                                    />
                                </div>

                                <Button type="submit" className="w-full">
                                    Save Mentor Profile
                                </Button>
                            </form>
                        </div>
                    </div>
                )}

                {/* Mentorship Request Modal Dialog */}
                {selectedMentor && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div className="w-full max-w-lg rounded-2xl border bg-card p-6 shadow-2xl space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-bold">
                                    Request Mentorship from {selectedMentor.member?.full_name}
                                </h3>
                                <Button size="sm" variant="ghost" onClick={() => setSelectedMentor(null)}>
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>

                            <form onSubmit={submitRequest} className="space-y-4">
                                <div>
                                    <label className="text-xs font-semibold">What topic do you want guidance on?</label>
                                    <Input
                                        placeholder="e.g. Applying for MS abroad, Resume review, Tech leadership"
                                        value={requestTopic}
                                        onChange={e => setRequestTopic(e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold">Message & Context</label>
                                    <textarea
                                        className="w-full rounded-md border bg-background p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                        rows={4}
                                        placeholder="Introduce yourself, your goals, and specific questions..."
                                        value={requestMessage}
                                        onChange={e => setRequestMessage(e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="flex justify-end gap-2">
                                    <Button type="button" variant="outline" onClick={() => setSelectedMentor(null)}>
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        Send Request <Send className="ml-1.5 h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </MemberLayout>
    );
}
