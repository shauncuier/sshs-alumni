import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    Briefcase,
    Building,
    Gift,
    GraduationCap,
    HeartHandshake,
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

    if (!member) {
        return (
            <MemberLayout title={t('admin.nav.dashboard')}>
                <Alert>
                    <AlertTitle>{t('common.states.empty')}</AlertTitle>
                    <AlertDescription>{t('public.nav.join')}</AlertDescription>
                </Alert>
            </MemberLayout>
        );
    }

    return (
        <MemberLayout title={t('admin.nav.dashboard')}>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">
                        {member.full_name}
                    </h1>
                    <Badge
                        variant={member.is_approved ? 'default' : 'secondary'}
                    >
                        {member.status_label}
                    </Badge>
                </div>

                {/* A member waiting on the committee gets an explanation, not
                    a locked door with no sign on it. */}
                {!member.is_approved && (
                    <Alert>
                        <AlertTitle>
                            {t(`member.status.${member.status}`)}
                        </AlertTitle>
                        <AlertDescription>
                            {t('member.approval_required')}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">
                                {t('member.profile.completion', {
                                    percent: formatNumber(
                                        member.profile_completion,
                                    ),
                                })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div
                                className="bg-muted h-2 w-full overflow-hidden rounded-full"
                                role="progressbar"
                                aria-valuenow={member.profile_completion}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            >
                                <div
                                    className="bg-brand-green-800 h-full rounded-full transition-all"
                                    style={{
                                        width: `${member.profile_completion}%`,
                                    }}
                                />
                            </div>

                            {member.missing_groups.length > 0 && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/my/profile">
                                        {t('common.actions.edit')}
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">
                                {t('admin.verification.assign_number')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {/* Membership numbers stay in Latin digits so they
                                can be quoted over the phone. */}
                            <p className="tabular-id text-xl font-semibold">
                                {member.membership_no ?? '—'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-muted-foreground text-sm font-medium">
                                {t('common.labels.batch')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-semibold">
                                {member.batch ?? '—'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {member.is_approved && (
                    <div className="space-y-4 pt-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold tracking-tight text-foreground">
                                Alumni Network & Services
                            </h2>
                            <span className="text-xs text-muted-foreground">
                                Exclusive Member Portals
                            </span>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Link
                                href="/donors"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-500/40 hover:shadow-md dark:hover:border-rose-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                                        <HeartHandshake className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                                        Blood Donors Directory
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        Lifesaving community network. Search verified donors by blood group or volunteer yourself.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    <span>Find Donors</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            <Link
                                href="/jobs"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-500/40 hover:shadow-md dark:hover:border-cyan-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-950/50 dark:text-cyan-400">
                                        <Briefcase className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                        Career & Job Board
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        Explore career openings shared by alumni or post job opportunities for your fellow graduates.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-cyan-600 dark:text-cyan-400">
                                    <span>Browse Jobs</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            <Link
                                href="/mentorship"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/40 hover:shadow-md dark:hover:border-amber-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                                        <GraduationCap className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                        Mentorship Network
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        Connect with industry leaders, request 1-on-1 career guidance, or register as an alumni mentor.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                    <span>Connect Mentors</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            <Link
                                href="/my/businesses"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-md dark:hover:border-emerald-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                        <Building className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        Alumni Business Directory
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        Showcase your business ventures, offer exclusive discounts to alumni, and discover peer enterprises.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    <span>Manage Listings</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            <Link
                                href="/my/certificates"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/40 hover:shadow-md dark:hover:border-purple-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                                        <Award className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                        Digital Certificates
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        View and share cryptographically verifiable certificates for events, honors, and memberships.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                    <span>View Certificates</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>

                            <Link
                                href="/campaigns"
                                className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/40 hover:shadow-md dark:hover:border-blue-500/50"
                            >
                                <div className="space-y-2">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                        <Gift className="size-5" />
                                    </div>
                                    <h3 className="text-base font-semibold text-foreground group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        Fundraising Campaigns
                                    </h3>
                                    <p className="text-xs text-muted-foreground line-clamp-2">
                                        Support flagship campus development, student scholarships, and Golden Jubilee celebration projects.
                                    </p>
                                </div>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    <span>Support Projects</span>
                                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </MemberLayout>
    );
}
