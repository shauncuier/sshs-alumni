import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    Briefcase,
    Building,
    Check,
    CheckCircle2,
    Clock,
    Copy,
    Gift,
    GraduationCap,
    HeartHandshake,
    IdCard,
    MessageSquare,
    QrCode,
    Sparkles,
    UserCheck,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';

type Member = {
    ulid: string;
    full_name: string;
    membership_no: string | null;
    status: string;
    status_label: string;
    is_approved: boolean;
    profile_completion: number;
    missing_groups: string[];
    batch: string | null;
};

export default function MemberDashboard({ member }: { member: Member | null }) {
    const { t } = useTranslation();
    const [copied, setCopied] = useState(false);

    const handleCopyMembership = () => {
        if (member?.membership_no) {
            navigator.clipboard.writeText(member.membership_no);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    if (!member) {
        return (
            <MemberLayout title={t('admin.nav.dashboard')}>
                <Alert className="border-amber-500/30 bg-amber-500/10 dark:bg-amber-950/20">
                    <AlertTitle>{t('common.states.empty')}</AlertTitle>
                    <AlertDescription>{t('public.nav.join')}</AlertDescription>
                </Alert>
            </MemberLayout>
        );
    }

    // Get time-based greeting
    const currentHour = new Date().getHours();
    const greeting =
        currentHour < 12
            ? 'Good morning'
            : currentHour < 17
            ? 'Good afternoon'
            : 'Good evening';

    return (
        <MemberLayout title={t('admin.nav.dashboard')}>
            <div className="space-y-8">
                {/* Hero Profile Banner */}
                <div className="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950/20 via-card to-emerald-900/10 p-6 md:p-8 shadow-sm backdrop-blur-md">
                    {/* Background glowing orb accents */}
                    <div className="pointer-events-none absolute -right-20 -top-20 size-72 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-500/15" />
                    <div className="pointer-events-none absolute -bottom-24 -left-20 size-80 rounded-full bg-teal-500/10 blur-3xl dark:bg-teal-500/15" />

                    <div className="relative z-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-start gap-4 sm:items-center">
                            {/* Monogram Avatar with pulse ring */}
                            <div className="relative flex size-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-700 to-teal-500 text-xl font-bold text-white shadow-md shadow-emerald-900/20">
                                {member.full_name
                                    .split(' ')
                                    .filter(Boolean)
                                    .slice(0, 2)
                                    .map((n) => n[0])
                                    .join('')
                                    .toUpperCase() || 'AL'}
                                {member.is_approved && (
                                    <span className="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-background">
                                        <Check className="size-3 stroke-[3]" />
                                    </span>
                                )}
                            </div>

                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                        {greeting}
                                    </span>
                                    {member.batch && (
                                        <span className="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            {member.batch}
                                        </span>
                                    )}
                                </div>
                                <h1 className="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                                    {member.full_name}
                                </h1>
                                <div className="flex flex-wrap items-center gap-2 pt-0.5">
                                    <Badge
                                        variant={member.is_approved ? 'default' : 'secondary'}
                                        className={
                                            member.is_approved
                                                ? 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 text-white font-medium gap-1.5'
                                                : 'gap-1.5'
                                        }
                                    >
                                        {member.is_approved ? (
                                            <>
                                                <span className="relative flex size-2">
                                                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75" />
                                                    <span className="relative inline-flex size-2 rounded-full bg-white" />
                                                </span>
                                                <span>{member.status_label}</span>
                                            </>
                                        ) : (
                                            <>
                                                <Clock className="size-3" />
                                                <span>{member.status_label}</span>
                                            </>
                                        )}
                                    </Badge>

                                    {member.membership_no && (
                                        <button
                                            type="button"
                                            onClick={handleCopyMembership}
                                            className="group inline-flex items-center gap-1.5 rounded-full border border-border/80 bg-background/80 px-3 py-0.5 text-xs font-mono font-medium text-foreground hover:bg-accent transition-colors"
                                            title="Click to copy Membership ID"
                                        >
                                            <span>ID: {member.membership_no}</span>
                                            {copied ? (
                                                <Check className="size-3 text-emerald-500" />
                                            ) : (
                                                <Copy className="size-3 text-muted-foreground group-hover:text-foreground transition-colors" />
                                            )}
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Fast Action Buttons */}
                        <div className="flex flex-wrap items-center gap-2.5">
                            {member.is_approved && (
                                <Button asChild variant="outline" className="gap-2 border-emerald-500/30 bg-background/80 hover:bg-emerald-500/10 hover:text-emerald-600">
                                    <Link href="/my/card">
                                        <IdCard className="size-4" />
                                        <span>Digital Card</span>
                                    </Link>
                                </Button>
                            )}
                            <Button asChild className="gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-sm shadow-emerald-900/20">
                                <Link href="/my/profile">
                                    <Sparkles className="size-4" />
                                    <span>Edit Profile</span>
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Pending Verification Notice */}
                {!member.is_approved && (
                    <Alert className="border-amber-500/30 bg-gradient-to-r from-amber-500/10 to-amber-500/5 dark:bg-amber-950/20">
                        <Clock className="size-4 text-amber-600 dark:text-amber-400" />
                        <AlertTitle className="text-amber-800 dark:text-amber-300 font-semibold">
                            {t(`member.status.${member.status}`)}
                        </AlertTitle>
                        <AlertDescription className="text-amber-700/90 dark:text-amber-400/90">
                            {t('member.approval_required')}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Key Overview Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Profile Completion Card */}
                    <Card className="relative overflow-hidden border-border/70 bg-card/70 backdrop-blur-xs transition-all hover:border-emerald-500/40">
                        <CardContent className="p-5 space-y-3">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground font-medium">Profile Strength</span>
                                <span className="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {formatNumber(member.profile_completion)}%
                                </span>
                            </div>

                            <div
                                className="bg-muted h-2.5 w-full overflow-hidden rounded-full"
                                role="progressbar"
                                aria-valuenow={member.profile_completion}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            >
                                <div
                                    className="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full transition-all duration-500"
                                    style={{ width: `${member.profile_completion}%` }}
                                />
                            </div>

                            <div className="flex items-center justify-between pt-1">
                                <span className="text-xs text-muted-foreground">
                                    {member.profile_completion === 100
                                        ? 'All sections complete'
                                        : `${member.missing_groups.length} item(s) pending`}
                                </span>
                                {member.missing_groups.length > 0 && (
                                    <Link
                                        href="/my/profile"
                                        className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1"
                                    >
                                        <span>Update</span>
                                        <ArrowRight className="size-3" />
                                    </Link>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Membership ID Card */}
                    <Card className="relative overflow-hidden border-border/70 bg-card/70 backdrop-blur-xs transition-all hover:border-emerald-500/40">
                        <CardContent className="p-5 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-sm font-medium">Membership ID</span>
                                <QrCode className="size-4 text-muted-foreground" />
                            </div>
                            <p className="tabular-id text-xl font-bold font-mono tracking-tight text-foreground">
                                {member.membership_no ?? 'Under Review'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {member.is_approved
                                    ? 'Official SSHS Alumni ID'
                                    : 'Assigned upon committee signoff'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Graduation Cohort */}
                    <Card className="relative overflow-hidden border-border/70 bg-card/70 backdrop-blur-xs transition-all hover:border-emerald-500/40">
                        <CardContent className="p-5 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-sm font-medium">Graduation Cohort</span>
                                <GraduationCap className="size-4 text-emerald-500" />
                            </div>
                            <p className="text-xl font-bold text-foreground">
                                {member.batch ?? 'General Member'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Sabuj Shikshayatan High School
                            </p>
                        </CardContent>
                    </Card>

                    {/* Security & Verification */}
                    <Card className="relative overflow-hidden border-border/70 bg-card/70 backdrop-blur-xs transition-all hover:border-emerald-500/40">
                        <CardContent className="p-5 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-sm font-medium">Account Standing</span>
                                <UserCheck className="size-4 text-teal-500" />
                            </div>
                            <p className="text-xl font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                {member.is_approved ? 'Verified Alumni' : 'Pending Verification'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {member.is_approved
                                    ? 'Full access to alumni networks'
                                    : 'Review in progress by admin'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Alumni Network & Portals Showcase */}
                {member.is_approved && (
                    <div className="space-y-5">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                    <Sparkles className="size-5 text-emerald-500" />
                                    <span>Alumni Network & Services</span>
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Exclusive interactive modules and opportunities for verified graduates
                                </p>
                            </div>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <span className="inline-block size-2 rounded-full bg-emerald-500" />
                                <span>All modules live & operational</span>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {/* Blood Donors Directory */}
                            <Link
                                href="/donors"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-rose-500/20 bg-gradient-to-b from-card to-rose-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-rose-500/50 hover:shadow-lg hover:shadow-rose-950/10 dark:hover:border-rose-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400 ring-1 ring-rose-500/20">
                                            <HeartHandshake className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-rose-500/10 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                            Lifesaving
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                                        Blood Donors Directory
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        Lifesaving community network. Search verified donors by blood group or volunteer yourself.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    <span>Find Donors</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            {/* Job & Career Board */}
                            <Link
                                href="/jobs"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-cyan-500/20 bg-gradient-to-b from-card to-cyan-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-cyan-500/50 hover:shadow-lg hover:shadow-cyan-950/10 dark:hover:border-cyan-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-600 dark:bg-cyan-950/50 dark:text-cyan-400 ring-1 ring-cyan-500/20">
                                            <Briefcase className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-cyan-500/10 px-2 py-0.5 text-[11px] font-semibold text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300">
                                            Career
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                        Career & Job Board
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        Explore career openings shared by alumni or post job opportunities for your fellow graduates.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-cyan-600 dark:text-cyan-400">
                                    <span>Browse Jobs</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            {/* Mentorship Network */}
                            <Link
                                href="/mentorship"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-amber-500/20 bg-gradient-to-b from-card to-amber-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/50 hover:shadow-lg hover:shadow-amber-950/10 dark:hover:border-amber-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 ring-1 ring-amber-500/20">
                                            <GraduationCap className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                            1-on-1 Guidance
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                        Mentorship Network
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        Connect with industry leaders, request 1-on-1 career guidance, or register as an alumni mentor.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                    <span>Connect Mentors</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            {/* Business Directory */}
                            <Link
                                href="/my/businesses"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-emerald-500/20 bg-gradient-to-b from-card to-emerald-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-emerald-500/50 hover:shadow-lg hover:shadow-emerald-950/10 dark:hover:border-emerald-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 ring-1 ring-emerald-500/20">
                                            <Building className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            Enterprises
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        Alumni Business Directory
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        Showcase your business ventures, offer exclusive discounts to alumni, and discover peer enterprises.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    <span>Manage Listings</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            {/* Verifiable Certificates */}
                            <Link
                                href="/my/certificates"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-purple-500/20 bg-gradient-to-b from-card to-purple-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-950/10 dark:hover:border-purple-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400 ring-1 ring-purple-500/20">
                                            <Award className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-purple-500/10 px-2 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                                            Credentials
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                        Digital Certificates
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        View and share cryptographically verifiable certificates for events, honors, and memberships.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                    <span>View Certificates</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            {/* Fundraising Campaigns */}
                            <Link
                                href="/campaigns"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-b from-card to-blue-950/5 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-lg hover:shadow-blue-950/10 dark:hover:border-blue-500/60"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 ring-1 ring-blue-500/20">
                                            <Gift className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-blue-500/10 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                            Impact
                                        </span>
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        Fundraising Campaigns
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        Support flagship campus development, student scholarships, and Golden Jubilee celebration projects.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center justify-between border-t border-border/50 pt-3 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    <span>Support Projects</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>
                        </div>
                    </div>
                )}

                {/* Community & Quick Links Footer Strip */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Link
                        href="/community"
                        className="group flex items-center justify-between rounded-2xl border border-border/70 bg-card/60 p-4 transition-all hover:border-emerald-500/40 hover:bg-card/90"
                    >
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <MessageSquare className="size-5" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-foreground group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    Community Feed
                                </h4>
                                <p className="text-xs text-muted-foreground">Share memories & discuss</p>
                            </div>
                        </div>
                        <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-foreground" />
                    </Link>

                    <Link
                        href="/events"
                        className="group flex items-center justify-between rounded-2xl border border-border/70 bg-card/60 p-4 transition-all hover:border-emerald-500/40 hover:bg-card/90"
                    >
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                                <Users className="size-5" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-foreground group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                                    Upcoming Reunions
                                </h4>
                                <p className="text-xs text-muted-foreground">Browse & reserve passes</p>
                            </div>
                        </div>
                        <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-foreground" />
                    </Link>

                    <Link
                        href="/donate"
                        className="group flex items-center justify-between rounded-2xl border border-border/70 bg-card/60 p-4 transition-all hover:border-emerald-500/40 hover:bg-card/90"
                    >
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <Gift className="size-5" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                    Contribute to Alma Mater
                                </h4>
                                <p className="text-xs text-muted-foreground">Support future generations</p>
                            </div>
                        </div>
                        <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-foreground" />
                    </Link>
                </div>
            </div>
        </MemberLayout>
    );
}
