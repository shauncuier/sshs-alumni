import { router, useForm } from '@inertiajs/react';
import { HandHeart, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
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
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Option, Volunteer, VolunteerTeam } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    volunteers: Paginated<Volunteer>;
    teams: VolunteerTeam[];
    filters: Record<string, string | null>;
    options: {
        statuses: Option[];
        assignment_statuses: Option[];
        events: Option[];
    };
    can: { manage: boolean };
};

export default function VolunteersIndex({
    volunteers,
    teams,
    options,
    can,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.volunteers.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.volunteers.title')}
                    </h1>

                    {can.manage && <AddVolunteerForm />}
                </div>

                {teams.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('admin.volunteers.teams')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {teams.map((team) => (
                                    <span
                                        key={team.id}
                                        className="rounded-full border px-3 py-1 text-sm"
                                    >
                                        {team.name}
                                        <span className="text-muted-foreground ms-1.5 tabular-nums">
                                            {formatNumber(
                                                team.assignments_count,
                                            )}
                                        </span>
                                    </span>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {volunteers.data.length === 0 ? (
                    <EmptyState
                        icon={HandHeart}
                        title={t('common.states.empty')}
                        description={t('admin.volunteers.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {volunteers.data.map((volunteer) => (
                            <VolunteerRow
                                key={volunteer.id}
                                volunteer={volunteer}
                                teams={teams}
                                options={options}
                                can={can}
                            />
                        ))}
                    </div>
                )}

                <Pagination meta={volunteers.meta} />
            </div>
        </AdminLayout>
    );
}

function VolunteerRow({
    volunteer,
    teams,
    options,
    can,
}: {
    volunteer: Volunteer;
    teams: VolunteerTeam[];
    options: { statuses: Option[] };
    can: { manage: boolean };
}) {
    const { t } = useTranslation();
    const [assigning, setAssigning] = useState(false);

    const assign = useForm({ volunteer_team_id: '', responsibility: '' });

    return (
        <div className="space-y-3 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">{volunteer.name}</p>
                    <p className="text-muted-foreground truncate text-sm">
                        {volunteer.phone ?? volunteer.email ?? ''}
                        {volunteer.availability
                            ? ` · ${volunteer.availability}`
                            : ''}
                    </p>

                    {volunteer.assignments.length > 0 && (
                        <div className="mt-1 flex flex-wrap gap-1">
                            {volunteer.assignments.map((assignment) => (
                                <Badge
                                    key={assignment.id}
                                    variant="outline"
                                    className="text-xs"
                                >
                                    {assignment.team ??
                                        assignment.event ??
                                        assignment.responsibility ??
                                        assignment.status_label}
                                </Badge>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {can.manage ? (
                        <Select
                            value={volunteer.status}
                            onValueChange={(status) =>
                                router.put(
                                    `/admin/volunteers/${volunteer.id}`,
                                    { status },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <SelectTrigger className="h-8 w-auto min-w-32 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.statuses.map((status) => (
                                    <SelectItem
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : (
                        <Badge variant="secondary">
                            {volunteer.status_label}
                        </Badge>
                    )}

                    {can.manage && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setAssigning((on) => !on)}
                        >
                            {t('admin.volunteers.assign')}
                        </Button>
                    )}
                </div>
            </div>

            {assigning && (
                <form
                    onSubmit={(e: FormEvent) => {
                        e.preventDefault();
                        assign.post(
                            `/admin/volunteers/${volunteer.id}/assignments`,
                            {
                                preserveScroll: true,
                                onSuccess: () => setAssigning(false),
                            },
                        );
                    }}
                    className="bg-muted/40 flex flex-wrap gap-2 rounded-md p-3"
                >
                    <Select
                        value={assign.data.volunteer_team_id || '__none'}
                        onValueChange={(value) =>
                            assign.setData(
                                'volunteer_team_id',
                                value === '__none' ? '' : value,
                            )
                        }
                    >
                        <SelectTrigger className="w-auto min-w-40">
                            <SelectValue
                                placeholder={t('admin.volunteers.team')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none">
                                {t('admin.crm.no_subject')}
                            </SelectItem>
                            {teams.map((team) => (
                                <SelectItem
                                    key={team.id}
                                    value={String(team.id)}
                                >
                                    {team.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Input
                        value={assign.data.responsibility}
                        placeholder={t('admin.volunteers.responsibility')}
                        onChange={(e) =>
                            assign.setData('responsibility', e.target.value)
                        }
                        className="min-w-48 flex-1"
                    />

                    <Button
                        type="submit"
                        size="sm"
                        disabled={assign.processing}
                    >
                        {t('admin.volunteers.assign')}
                    </Button>
                </form>
            )}
        </div>
    );
}

function AddVolunteerForm() {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({ name: '', phone: '', email: '', availability: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/volunteers', {
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.volunteers.add')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <p className="text-muted-foreground text-sm">
                        {t('admin.volunteers.not_a_member_hint')}
                    </p>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="volunteer_name">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="volunteer_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="volunteer_phone">
                                {t('common.labels.phone')}
                            </Label>
                            <Input
                                id="volunteer_phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                            />
                            <InputError message={form.errors.phone} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="availability">
                                {t('admin.volunteers.availability')}
                            </Label>
                            <Input
                                id="availability"
                                value={form.data.availability}
                                onChange={(e) =>
                                    form.setData('availability', e.target.value)
                                }
                            />
                            <InputError message={form.errors.availability} />
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                        >
                            {t('common.actions.cancel')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
