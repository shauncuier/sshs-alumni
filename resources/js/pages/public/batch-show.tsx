import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Briefcase,
    ExternalLink,
    GraduationCap,
    Lock,
    MapPin,
    ShieldCheck,
    Sparkles,
    UserCheck,
    Users,
} from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import type { BatchCoordinator } from '@/types/batch';
import type { DirectoryMember, Paginated } from '@/types/member';

type Batch = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    ssc_year: number;
    members_count: number;
    cover_url: string | null;
};

type Props = {
    batch: Batch;
    can_view_members?: boolean;
    is_super_admin?: boolean;
    members?: Paginated<DirectoryMember> | null;
    coordinators?: BatchCoordinator[];
    admin_batch_url?: string | null;
};

/**
 * A single batch page for the public and authenticated alumni.
 *
 * For guests, shows aggregate counts and registration callouts.
 * For verified alumni and Super Admins, displays the cohort roster,
 * coordinators, and directory profiles.
 */
export default function BatchShow({
    batch,
    can_view_members = false,
    is_super_admin = false,
    members = null,
    coordinators = [],
    admin_batch_url = null,
}: Props) {
    const { t, choice } = useTranslation();
    const getInitials = useInitials();
    const page = usePage();
    const auth = page.props.auth as { user?: unknown } | undefined;
    const isAuthenticated = Boolean(auth?.user);

    return (
        <PublicLayout
            title={batch.name}
            description={batch.description ?? undefined}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                    >
                        <Link href="/batches">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.nav.batches')}
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center gap-3">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                            <GraduationCap className="size-3.5" />
                            SSC Class of {batch.ssc_year}
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3.5 py-1 text-xs font-medium text-cyan-200 backdrop-blur-md">
                            <Users className="size-3.5" aria-hidden="true" />
                            {choice(
                                'public.batches.member_count',
                                batch.members_count,
                                { count: formatNumber(batch.members_count) },
                            )}
                        </span>
                        {is_super_admin && (
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300 backdrop-blur-md">
                                <ShieldCheck className="size-3.5 text-amber-400" />
                                Super Admin Access
                            </span>
                        )}
                    </div>

                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {batch.name}
                    </h1>

                    {batch.description && (
                        <p className="mt-4 max-w-3xl text-base sm:text-lg leading-relaxed text-slate-300 font-light">
                            {batch.description}
                        </p>
                    )}
                </div>
            </div>

            {/* Main Content */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16 min-h-[600px]">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-10">

                    {/* Super Admin Quick Actions Bar */}
                    {is_super_admin && (
                        <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-400/30 bg-gradient-to-r from-amber-50/80 via-amber-100/40 to-teal-50/60 p-4 shadow-sm backdrop-blur-md">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                                    <ShieldCheck className="size-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-slate-900">
                                        Super Admin Privileges Active
                                    </h4>
                                    <p className="text-xs text-slate-600">
                                        You have full unrestricted visibility over this cohort&apos;s alumni roster and settings.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {admin_batch_url && (
                                    <Button
                                        asChild
                                        size="sm"
                                        variant="outline"
                                        className="rounded-xl border-amber-400/50 bg-white/80 text-amber-900 hover:bg-amber-100 font-medium text-xs shadow-xs"
                                    >
                                        <Link href={admin_batch_url}>
                                            <ExternalLink className="mr-1.5 size-3.5" />
                                            Manage in Admin Panel
                                        </Link>
                                    </Button>
                                )}
                                <Button
                                    asChild
                                    size="sm"
                                    className="rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 text-white font-medium text-xs shadow-sm hover:from-teal-700 hover:to-cyan-700"
                                >
                                    <Link href={`/admin/members?batch_id=${batch.id}`}>
                                        <Users className="mr-1.5 size-3.5" />
                                        View in Admin Roster
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* Batch Coordinators Section */}
                    {can_view_members && coordinators.length > 0 && (
                        <section className="space-y-4">
                            <div className="flex items-center gap-2">
                                <UserCheck className="size-5 text-teal-600" />
                                <h2 className="text-xl font-bold tracking-tight text-slate-900">
                                    {t('member.batch.coordinators')}
                                </h2>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                {coordinators.map((coordinator) => (
                                    <Link
                                        key={coordinator.ulid}
                                        href={`/directory/${coordinator.ulid}`}
                                        className="group inline-flex items-center gap-3 rounded-full border border-teal-500/25 bg-white px-3.5 py-1.5 shadow-sm transition hover:border-teal-500 hover:shadow-md"
                                    >
                                        <Avatar className="size-8 ring-1 ring-teal-500/20">
                                            {coordinator.photo_url !== null && (
                                                <AvatarImage
                                                    src={coordinator.photo_url}
                                                    alt={coordinator.name}
                                                />
                                            )}
                                            <AvatarFallback className="bg-teal-50 text-teal-700 text-xs font-semibold">
                                                {getInitials(coordinator.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="text-start">
                                            <p className="text-xs font-bold text-slate-900 group-hover:text-teal-600 transition">
                                                {coordinator.name}
                                            </p>
                                            {coordinator.membership_no && (
                                                <p className="text-[10px] text-slate-500 tabular-nums">
                                                    {coordinator.membership_no}
                                                </p>
                                            )}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}

                    {/* Cohort Members Section (When Allowed) */}
                    {can_view_members ? (
                        <section className="space-y-6">
                            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                                <div>
                                    <h2 className="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                                        <GraduationCap className="size-5 text-teal-600" />
                                        {t('member.batch.members')}
                                    </h2>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {is_super_admin
                                            ? 'Displaying all registered members enrolled in this batch.'
                                            : t('member.batch.hidden_note')}
                                    </p>
                                </div>

                                <Badge variant="secondary" className="px-3 py-1 font-semibold text-xs">
                                    {choice(
                                        'public.batches.member_count',
                                        batch.members_count,
                                        { count: formatNumber(batch.members_count) },
                                    )}
                                </Badge>
                            </div>

                            {members === null || members.data.length === 0 ? (
                                <EmptyState
                                    title={t('common.states.empty')}
                                    description={t('member.directory.empty')}
                                />
                            ) : (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {members.data.map((member) => (
                                            <Card
                                                key={member.ulid}
                                                className="group border border-slate-200/80 bg-white/90 hover:border-teal-500/50 hover:shadow-md transition-all duration-200 rounded-2xl overflow-hidden"
                                            >
                                                <CardContent className="flex items-start gap-4 p-5">
                                                    <Avatar className="size-14 shrink-0 ring-2 ring-slate-100 group-hover:ring-teal-500/30 transition">
                                                        {member.photo_url !== null && (
                                                            <AvatarImage
                                                                src={member.photo_url}
                                                                alt={member.full_name}
                                                            />
                                                        )}
                                                        <AvatarFallback className="bg-gradient-to-br from-teal-50 to-cyan-100 text-teal-800 font-bold text-sm">
                                                            {getInitials(member.full_name)}
                                                        </AvatarFallback>
                                                    </Avatar>

                                                    <div className="min-w-0 flex-1 space-y-1">
                                                        <Link
                                                            href={`/directory/${member.ulid}`}
                                                            className="block truncate font-bold text-slate-900 group-hover:text-teal-600 transition"
                                                        >
                                                            {member.full_name}
                                                        </Link>

                                                        {member.job_title && (
                                                            <p className="flex items-center gap-1.5 text-xs text-slate-600 truncate">
                                                                <Briefcase className="size-3 text-slate-400 shrink-0" />
                                                                <span className="truncate">{member.job_title}</span>
                                                            </p>
                                                        )}

                                                        {member.organization && (
                                                            <p className="text-xs text-slate-500 truncate">
                                                                {member.organization}
                                                            </p>
                                                        )}

                                                        {(member.city || member.district) && (
                                                            <p className="flex items-center gap-1 text-[11px] text-slate-400 truncate">
                                                                <MapPin className="size-3 shrink-0" />
                                                                <span className="truncate">
                                                                    {[member.city, member.district].filter(Boolean).join(', ')}
                                                                </span>
                                                            </p>
                                                        )}

                                                        {member.membership_no && (
                                                            <div className="pt-1">
                                                                <span className="inline-block rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-mono text-slate-600 font-semibold tracking-wide">
                                                                    {member.membership_no}
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>

                                    <div className="pt-4">
                                        <Pagination meta={members.meta} />
                                    </div>
                                </>
                            )}
                        </section>
                    ) : (
                        /* Directory Lock Callout for Non-Approved / Guest Users */
                        <div className="relative overflow-hidden rounded-3xl border border-teal-500/25 bg-gradient-to-br from-teal-50 via-white to-cyan-50/40 p-6 sm:p-10 shadow-sm backdrop-blur-md">
                            <div className="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                                <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-md shadow-teal-700/20">
                                    <Lock className="size-7" aria-hidden="true" />
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-lg sm:text-xl font-bold text-slate-900">
                                        {isAuthenticated
                                            ? 'Membership Verification in Progress'
                                            : 'Members Directory is Protected'}
                                    </h3>
                                    <p className="mt-1.5 text-sm text-slate-600 leading-relaxed">
                                        {isAuthenticated
                                            ? 'Your alumni application is awaiting verification by the committee. Once approved, the full cohort directory will be unlocked for you.'
                                            : t('public.directory.members_only_note')}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto shrink-0">
                                    {isAuthenticated ? (
                                        <Button
                                            asChild
                                            className="w-full sm:w-auto bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 text-white font-semibold shadow-md shadow-teal-500/25 hover:from-teal-600 hover:to-cyan-700 rounded-xl px-6 py-2.5 transition"
                                        >
                                            <Link href="/my/profile">
                                                View Application Status
                                            </Link>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="w-full sm:w-auto rounded-xl border-teal-500/30 font-semibold px-5 py-2.5 hover:bg-teal-50 transition"
                                            >
                                                <Link href="/login">
                                                    {t('common.actions.login')}
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                className="w-full sm:w-auto bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 text-white font-semibold shadow-md shadow-teal-500/25 hover:from-teal-600 hover:to-cyan-700 rounded-xl px-6 py-2.5 transition"
                                            >
                                                <Link href="/join">
                                                    <Sparkles className="mr-2 size-4" />
                                                    {t('public.nav.join')}
                                                </Link>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </PublicLayout>
    );
}
