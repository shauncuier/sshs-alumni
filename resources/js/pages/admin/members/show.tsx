import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Lock, ShieldCheck } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminMember } from '@/types/member';

type Props = {
    member: AdminMember;
    canVerify: boolean;
    allowedTransitions: { value: string; label: string }[];
};

export default function MemberShow({
    member,
    canVerify,
    allowedTransitions,
}: Props) {
    const { t, locale } = useTranslation();
    const getInitials = useInitials();

    return (
        <AdminLayout title={member.full_name}>
            <div className="space-y-6">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/admin/members">
                        <ArrowLeft
                            className="me-1 size-4 rtl:rotate-180"
                            aria-hidden="true"
                        />
                        {t('admin.nav.members')}
                    </Link>
                </Button>

                <div className="grid gap-6 lg:grid-cols-[1fr_22rem]">
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="flex gap-5 p-6">
                                <Avatar className="size-20 shrink-0">
                                    {member.photo_url && (
                                        <AvatarImage
                                            src={member.photo_url}
                                            alt=""
                                        />
                                    )}
                                    <AvatarFallback className="text-lg">
                                        {getInitials(member.full_name)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="min-w-0 space-y-2">
                                    <h1 className="text-xl font-semibold">
                                        {member.full_name}
                                    </h1>
                                    {member.full_name_bn && (
                                        <p
                                            lang="bn"
                                            className="text-muted-foreground"
                                        >
                                            {member.full_name_bn}
                                        </p>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        <Badge>{member.status_label}</Badge>
                                        {member.batch && (
                                            <Badge variant="secondary">
                                                {member.batch}
                                            </Badge>
                                        )}
                                        {member.membership_no && (
                                            <Badge
                                                variant="outline"
                                                className="tabular-id"
                                            >
                                                {member.membership_no}
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold">
                                    {t('public.join.steps.basic')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                    <Row label={t('public.join.fields.email')}>
                                        {member.email}
                                    </Row>
                                    <Row label={t('public.join.fields.mobile')}>
                                        <span className="tabular-id">
                                            {member.mobile}
                                        </span>
                                    </Row>
                                    <Row
                                        label={t(
                                            'public.join.fields.relation_type',
                                        )}
                                    >
                                        {member.relation_label}
                                    </Row>
                                    <Row
                                        label={t(
                                            'public.join.fields.blood_group',
                                        )}
                                    >
                                        {member.blood_group}
                                    </Row>
                                    <Row
                                        label={t(
                                            'public.join.fields.occupation',
                                        )}
                                    >
                                        {member.occupation}
                                    </Row>
                                    <Row
                                        label={t(
                                            'public.join.fields.organization',
                                        )}
                                    >
                                        {member.organization}
                                    </Row>
                                    <Row
                                        label={t('public.join.fields.district')}
                                    >
                                        {member.district}
                                    </Row>
                                    <Row label={t('public.join.fields.city')}>
                                        {member.city}
                                    </Row>
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold">
                                    {t('admin.verification.history')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!member.verifications ||
                                member.verifications.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        {t('common.states.empty')}
                                    </p>
                                ) : (
                                    <ol className="space-y-4">
                                        {member.verifications.map((entry) => (
                                            <li
                                                key={entry.id}
                                                className="border-s-2 ps-4 text-sm"
                                            >
                                                <p className="font-medium">
                                                    {entry.from_label
                                                        ? `${entry.from_label} → ${entry.to_label}`
                                                        : entry.to_label}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {formatDate(
                                                        entry.created_at,
                                                        locale,
                                                        {
                                                            dateStyle: 'medium',
                                                            timeStyle: 'short',
                                                        },
                                                    )}
                                                    {entry.actor
                                                        ? ` · ${entry.actor}`
                                                        : ''}
                                                </p>
                                                {entry.correction_requested && (
                                                    <p className="mt-1">
                                                        {
                                                            entry.correction_requested
                                                        }
                                                    </p>
                                                )}
                                                {entry.note && (
                                                    <p className="text-muted-foreground mt-1 flex items-start gap-1.5 text-xs">
                                                        <Lock
                                                            className="mt-0.5 size-3 shrink-0"
                                                            aria-hidden="true"
                                                        />
                                                        {entry.note}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ol>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {canVerify ? (
                        <VerificationPanel
                            member={member}
                            allowedTransitions={allowedTransitions}
                        />
                    ) : (
                        <Card>
                            <CardContent className="text-muted-foreground p-6 text-sm">
                                {t('admin.verification.no_permission')}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="mt-0.5 break-words">{children || '—'}</dd>
        </div>
    );
}

function VerificationPanel({
    member,
    allowedTransitions,
}: {
    member: AdminMember;
    allowedTransitions: { value: string; label: string }[];
}) {
    const { t } = useTranslation();

    const transition = useForm({ status: '', note: '' });
    const correction = useForm({ message: '', note: '' });
    const number = useForm({ membership_no: '' });

    const submitTransition = (event: FormEvent) => {
        event.preventDefault();
        transition.post(`/admin/members/${member.ulid}/transition`, {
            preserveScroll: true,
            onSuccess: () => transition.reset(),
        });
    };

    const submitCorrection = (event: FormEvent) => {
        event.preventDefault();
        correction.post(`/admin/members/${member.ulid}/request-correction`, {
            preserveScroll: true,
            onSuccess: () => correction.reset(),
        });
    };

    const submitNumber = (event: FormEvent) => {
        event.preventDefault();
        number.post(`/admin/members/${member.ulid}/membership-number`, {
            preserveScroll: true,
            onSuccess: () => number.reset(),
        });
    };

    return (
        <div className="space-y-4">
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                        <ShieldCheck className="size-4" aria-hidden="true" />
                        {t('admin.nav.verification')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submitTransition} className="space-y-3">
                        <div className="space-y-1.5">
                            <Label>{t('common.labels.status')}</Label>
                            <Select
                                value={transition.data.status}
                                onValueChange={(value) =>
                                    transition.setData('status', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={t(
                                            'common.actions.confirm',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {allowedTransitions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={transition.errors.status} />
                        </div>

                        <div className="space-y-1.5">
                            <Label>
                                {t('admin.verification.internal_note')}
                            </Label>
                            <textarea
                                rows={3}
                                className="border-input focus-visible:ring-ring w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                value={transition.data.note}
                                onChange={(event) =>
                                    transition.setData(
                                        'note',
                                        event.target.value,
                                    )
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                {t('admin.verification.internal_note_hint')}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={
                                transition.processing || !transition.data.status
                            }
                        >
                            {t('common.actions.confirm')}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-semibold">
                        {t('admin.verification.request_correction')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submitCorrection} className="space-y-3">
                        <textarea
                            rows={3}
                            className="border-input focus-visible:ring-ring w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            value={correction.data.message}
                            onChange={(event) =>
                                correction.setData(
                                    'message',
                                    event.target.value,
                                )
                            }
                            placeholder={t(
                                'admin.verification.correction_placeholder',
                            )}
                        />
                        <InputError message={correction.errors.message} />

                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                            disabled={correction.processing}
                        >
                            {t('common.actions.submit')}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-semibold">
                        {t('admin.verification.assign_number')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submitNumber} className="space-y-3">
                        <Input
                            value={number.data.membership_no}
                            onChange={(event) =>
                                number.setData(
                                    'membership_no',
                                    event.target.value,
                                )
                            }
                            placeholder={t(
                                'admin.verification.number_placeholder',
                            )}
                            className="tabular-id"
                        />
                        <InputError message={number.errors.membership_no} />

                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                            disabled={number.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
