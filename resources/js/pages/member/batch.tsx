import { Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { BatchCoordinator, MemberBatch } from '@/types/batch';
import type { DirectoryMember, Paginated } from '@/types/member';

type Props = {
    batch: MemberBatch | null;
    members: Paginated<DirectoryMember> | null;
    coordinators: BatchCoordinator[];
};

export default function Batch({ batch, members, coordinators }: Props) {
    const { t, locale } = useTranslation();

    if (batch === null) {
        return (
            <MemberLayout title={t('member.batch.title')}>
                <EmptyState
                    title={t('member.batch.title')}
                    description={t('member.batch.none')}
                />
            </MemberLayout>
        );
    }

    return (
        <MemberLayout title={batch.name}>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <GraduationCap
                        className="text-brand-green-700 size-6"
                        aria-hidden="true"
                    />
                    <h1 lang={locale} className="text-2xl font-semibold">
                        {batch.name}
                    </h1>
                    <Badge variant="secondary">
                        {t('member.directory.count', {
                            count: formatNumber(batch.members_count, locale),
                        })}
                    </Badge>
                </div>

                {batch.description !== null && (
                    <p
                        lang={locale}
                        className="text-muted-foreground max-w-3xl text-sm leading-relaxed"
                    >
                        {batch.description}
                    </p>
                )}

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">
                        {t('member.batch.coordinators')}
                    </h2>

                    {coordinators.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {t('member.batch.no_coordinators')}
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-3">
                            {coordinators.map((coordinator) => (
                                <PersonChip
                                    key={coordinator.ulid}
                                    coordinator={coordinator}
                                />
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {t('member.batch.members')}
                        </h2>
                        {/* Stated plainly, because the list will not match the
                            count above and a member would otherwise assume a
                            bug. */}
                        <p className="text-muted-foreground mt-1 text-xs">
                            {t('member.batch.hidden_note')}
                        </p>
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
                                    <MemberCard
                                        key={member.ulid}
                                        member={member}
                                    />
                                ))}
                            </div>

                            <Pagination meta={members.meta} />
                        </>
                    )}
                </section>
            </div>
        </MemberLayout>
    );
}

function PersonChip({ coordinator }: { coordinator: BatchCoordinator }) {
    const getInitials = useInitials();

    return (
        <Link
            href={`/directory/${coordinator.ulid}`}
            className="hover:bg-accent flex items-center gap-2 rounded-full border px-3 py-1.5 transition-colors"
        >
            <Avatar className="size-7">
                {coordinator.photo_url !== null && (
                    <AvatarImage src={coordinator.photo_url} alt="" />
                )}
                <AvatarFallback className="text-xs">
                    {getInitials(coordinator.name)}
                </AvatarFallback>
            </Avatar>
            <span className="text-sm font-medium">{coordinator.name}</span>
        </Link>
    );
}

function MemberCard({ member }: { member: DirectoryMember }) {
    const getInitials = useInitials();

    return (
        <Card className="hover:border-brand-green-300 transition-colors">
            <CardContent className="flex items-start gap-3 p-4">
                <Avatar className="size-12 shrink-0">
                    {member.photo_url !== null && (
                        <AvatarImage src={member.photo_url} alt="" />
                    )}
                    <AvatarFallback>
                        {getInitials(member.full_name)}
                    </AvatarFallback>
                </Avatar>

                <div className="min-w-0 flex-1">
                    <Link
                        href={`/directory/${member.ulid}`}
                        className="block truncate font-medium hover:underline"
                    >
                        {member.full_name}
                    </Link>

                    {member.job_title != null && (
                        <p className="text-muted-foreground truncate text-sm">
                            {member.job_title}
                        </p>
                    )}

                    {member.organization != null && (
                        <p className="text-muted-foreground truncate text-xs">
                            {member.organization}
                        </p>
                    )}

                    {member.membership_no !== null && (
                        <p className="tabular-id text-muted-foreground mt-1 truncate text-xs">
                            {member.membership_no}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
