import { Link } from '@inertiajs/react';
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
    const { t, locale } = useTranslation();

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
        <MemberLayout
            title={t('admin.nav.dashboard')}
            approved={member.is_approved}
        >
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
                                        locale,
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
            </div>
        </MemberLayout>
    );
}
