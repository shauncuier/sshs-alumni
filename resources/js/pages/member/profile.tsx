import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { AdminMember } from '@/types/member';

type Option = { value: string; label: string };

type Props = {
    member: AdminMember;
    completion: { percent: number; missing: string[] };
    options: {
        genders: Option[];
        blood_groups: Option[];
        link_types: Option[];
        batches: Option[];
    };
};

const PRIVACY_FLAGS = [
    'show_profile',
    'show_phone',
    'show_email',
    'show_workplace',
    'show_location',
    'show_date_of_birth',
    'show_in_batch_list',
] as const;

export default function Profile({ member, completion, options }: Props) {
    const { t, locale } = useTranslation();

    return (
        <MemberLayout
            title={t('member.nav.profile')}
            approved={member.status === 'approved'}
        >
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {t('member.nav.profile')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('member.profile.completion', {
                            percent: formatNumber(completion.percent, locale),
                        })}
                    </p>
                </div>

                <ProfileForm member={member} options={options} />
                <PrivacyForm member={member} />
            </div>
        </MemberLayout>
    );
}

function ProfileForm({
    member,
    options,
}: {
    member: AdminMember;
    options: Props['options'];
}) {
    const { t } = useTranslation();

    const form = useForm({
        full_name: member.full_name ?? '',
        full_name_bn: member.full_name_bn ?? '',
        date_of_birth: member.date_of_birth ?? '',
        gender: member.gender ?? '',
        blood_group: member.blood_group ?? '',
        batch_id: member.batch_id ? String(member.batch_id) : '',
        ssc_year: member.ssc_year ? String(member.ssc_year) : '',
        occupation: member.occupation ?? '',
        organization: member.organization ?? '',
        job_title: member.job_title ?? '',
        industry: member.industry ?? '',
        country: member.country ?? '',
        division: member.division ?? '',
        district: member.district ?? '',
        city: member.city ?? '',
        address: member.address ?? '',
        mobile: member.mobile ?? '',
        whatsapp: member.whatsapp ?? '',
        email: member.email ?? '',
        emergency_contact_name: member.emergency_contact_name ?? '',
        emergency_contact_phone: member.emergency_contact_phone ?? '',
        bio: member.bio ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch('/my/profile', { preserveScroll: true });
    };

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {t('member.nav.profile')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label={t('public.join.fields.full_name')}
                            error={form.errors.full_name}
                        >
                            <Input
                                value={form.data.full_name}
                                onChange={(e) =>
                                    form.setData('full_name', e.target.value)
                                }
                                required
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.full_name_bn')}
                            error={form.errors.full_name_bn}
                        >
                            <Input
                                lang="bn"
                                value={form.data.full_name_bn}
                                onChange={(e) =>
                                    form.setData('full_name_bn', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field
                            label={t('public.join.fields.gender')}
                            error={form.errors.gender}
                        >
                            <Choice
                                options={options.genders}
                                value={form.data.gender}
                                onChange={(value) =>
                                    form.setData('gender', value)
                                }
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.blood_group')}
                            error={form.errors.blood_group}
                        >
                            <Choice
                                options={options.blood_groups}
                                value={form.data.blood_group}
                                onChange={(value) =>
                                    form.setData('blood_group', value)
                                }
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.date_of_birth')}
                            error={form.errors.date_of_birth}
                        >
                            <Input
                                type="date"
                                value={form.data.date_of_birth}
                                onChange={(e) =>
                                    form.setData(
                                        'date_of_birth',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label={t('public.join.fields.occupation')}
                            error={form.errors.occupation}
                        >
                            <Input
                                value={form.data.occupation}
                                onChange={(e) =>
                                    form.setData('occupation', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.organization')}
                            error={form.errors.organization}
                        >
                            <Input
                                value={form.data.organization}
                                onChange={(e) =>
                                    form.setData('organization', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label={t('public.join.fields.district')}
                            error={form.errors.district}
                        >
                            <Input
                                value={form.data.district}
                                onChange={(e) =>
                                    form.setData('district', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.city')}
                            error={form.errors.city}
                        >
                            <Input
                                value={form.data.city}
                                onChange={(e) =>
                                    form.setData('city', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label={t('public.join.fields.mobile')}
                            error={form.errors.mobile}
                        >
                            <Input
                                className="tabular-id"
                                value={form.data.mobile}
                                onChange={(e) =>
                                    form.setData('mobile', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label={t('public.join.fields.email')}
                            error={form.errors.email}
                        >
                            <Input
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <Field
                        label={t('public.join.fields.bio')}
                        error={form.errors.bio}
                    >
                        <textarea
                            rows={4}
                            className="border-input focus-visible:ring-ring w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            value={form.data.bio}
                            onChange={(e) =>
                                form.setData('bio', e.target.value)
                            }
                        />
                    </Field>

                    <div className="flex items-center gap-3 border-t pt-4">
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                        {form.recentlySuccessful && (
                            <span className="text-muted-foreground text-sm">
                                {t('common.states.saved')}
                            </span>
                        )}
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

/**
 * Privacy is its own form and its own endpoint.
 *
 * A member changing what the world can see should not have to resubmit their
 * whole profile to do it.
 */
function PrivacyForm({ member }: { member: AdminMember }) {
    const { t } = useTranslation();

    const form = useForm<Record<string, boolean>>({
        show_profile: member.privacy?.show_profile ?? true,
        show_phone: member.privacy?.show_phone ?? false,
        show_email: member.privacy?.show_email ?? false,
        show_workplace: member.privacy?.show_workplace ?? true,
        show_location: member.privacy?.show_location ?? true,
        show_date_of_birth: member.privacy?.show_date_of_birth ?? false,
        show_in_batch_list: member.privacy?.show_in_batch_list ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch('/my/privacy', { preserveScroll: true });
    };

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {t('public.join.privacy.legend')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-muted-foreground text-sm">
                        {t('public.join.privacy.note')}
                    </p>

                    <div className="space-y-3">
                        {PRIVACY_FLAGS.map((flag) => (
                            <div key={flag} className="flex items-center gap-2">
                                <Checkbox
                                    id={`privacy-${flag}`}
                                    checked={form.data[flag]}
                                    onCheckedChange={(checked) =>
                                        form.setData(flag, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`privacy-${flag}`}
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    {t(`public.join.privacy.${flag}`)}
                                </Label>
                            </div>
                        ))}
                    </div>

                    <div className="flex items-center gap-3 border-t pt-4">
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                        {form.recentlySuccessful && (
                            <span className="text-muted-foreground text-sm">
                                {t('common.states.saved')}
                            </span>
                        )}
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-sm font-medium">{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function Choice({
    options,
    value,
    onChange,
}: {
    options: Option[];
    value: string;
    onChange: (value: string) => void;
}) {
    const { t } = useTranslation();

    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger>
                <SelectValue placeholder={t('common.states.optional')} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
