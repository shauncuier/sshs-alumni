import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Trash2, UserPlus } from 'lucide-react';
import type { FormEvent } from 'react';
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
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminBatchDetail, Option } from '@/types/batch';

type Props = {
    batch: AdminBatchDetail;
    candidates: Option[];
    options: { statuses: Option[] };
    can: { update: boolean };
};

export default function BatchShow({ batch, candidates, options, can }: Props) {
    const { t, locale } = useTranslation();

    return (
        <AdminLayout title={batch.display_name}>
            <div className="space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/admin/batches">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('admin.nav.batches')}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-center gap-3">
                    <h1 lang={locale} className="text-2xl font-semibold">
                        {batch.display_name}
                    </h1>
                    <Badge
                        variant={
                            batch.status === 'active' ? 'default' : 'outline'
                        }
                    >
                        {batch.status_label}
                    </Badge>
                    <span className="text-muted-foreground text-sm">
                        {t('admin.batches.members_count')}:{' '}
                        {formatNumber(batch.members_count, locale)}
                    </span>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <DetailsForm
                        batch={batch}
                        options={options}
                        editable={can.update}
                    />
                    <CoordinatorPanel
                        batch={batch}
                        candidates={candidates}
                        editable={can.update}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}

function DetailsForm({
    batch,
    options,
    editable,
}: {
    batch: AdminBatchDetail;
    options: { statuses: Option[] };
    editable: boolean;
}) {
    const { t } = useTranslation();

    const form = useForm({
        name: batch.name,
        name_bn: batch.name_bn ?? '',
        ssc_year: String(batch.ssc_year),
        description: batch.description ?? '',
        description_bn: batch.description_bn ?? '',
        status: batch.status,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/batches/${batch.id}`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {t('admin.batches.details')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">
                                {t('common.labels.name')} (EN)
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                disabled={!editable}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="name_bn">
                                {t('common.labels.name')} (বাংলা)
                            </Label>
                            <Input
                                id="name_bn"
                                lang="bn"
                                value={form.data.name_bn}
                                disabled={!editable}
                                onChange={(event) =>
                                    form.setData('name_bn', event.target.value)
                                }
                            />
                            <InputError message={form.errors.name_bn} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ssc_year">
                                {t('admin.batches.ssc_year')}
                            </Label>
                            <Input
                                id="ssc_year"
                                type="number"
                                inputMode="numeric"
                                value={form.data.ssc_year}
                                disabled={!editable}
                                onChange={(event) =>
                                    form.setData('ssc_year', event.target.value)
                                }
                            />
                            <InputError message={form.errors.ssc_year} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="status">
                                {t('common.labels.status')}
                            </Label>
                            <Select
                                value={form.data.status}
                                disabled={!editable}
                                onValueChange={(value) =>
                                    form.setData('status', value)
                                }
                            >
                                <SelectTrigger id="status">
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
                            <InputError message={form.errors.status} />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="description">
                            {t('common.labels.description')} (EN)
                        </Label>
                        <textarea
                            id="description"
                            rows={3}
                            value={form.data.description}
                            disabled={!editable}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="description_bn">
                            {t('common.labels.description')} (বাংলা)
                        </Label>
                        <textarea
                            id="description_bn"
                            lang="bn"
                            rows={3}
                            value={form.data.description_bn}
                            disabled={!editable}
                            onChange={(event) =>
                                form.setData(
                                    'description_bn',
                                    event.target.value,
                                )
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none disabled:opacity-50"
                        />
                        <InputError message={form.errors.description_bn} />
                    </div>

                    {editable && (
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                    )}
                </CardContent>
            </Card>
        </form>
    );
}

/**
 * Coordinator assignment.
 *
 * The candidate list is built server-side from approved members of THIS batch
 * only, so the select cannot offer someone who would be rejected on submit.
 */
function CoordinatorPanel({
    batch,
    candidates,
    editable,
}: {
    batch: AdminBatchDetail;
    candidates: Option[];
    editable: boolean;
}) {
    const { t } = useTranslation();
    const getInitials = useInitials();

    const form = useForm({ member_id: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(`/admin/batches/${batch.id}/coordinators`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const remove = (ulid: string) => {
        router.delete(`/admin/batches/${batch.id}/coordinators/${ulid}`, {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('admin.batches.coordinators')}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="text-muted-foreground text-sm">
                    {t('admin.batches.coordinator_note')}
                </p>

                {batch.coordinators.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {t('admin.batches.no_coordinators')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {batch.coordinators.map((coordinator) => (
                            <li
                                key={coordinator.ulid}
                                className="flex items-center gap-3 p-3"
                            >
                                <Avatar className="size-8 shrink-0">
                                    {coordinator.photo_url !== null && (
                                        <AvatarImage
                                            src={coordinator.photo_url}
                                            alt=""
                                        />
                                    )}
                                    <AvatarFallback>
                                        {getInitials(coordinator.name)}
                                    </AvatarFallback>
                                </Avatar>

                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {coordinator.name}
                                    </span>
                                    {coordinator.membership_no !== null && (
                                        <span className="tabular-id text-muted-foreground block truncate text-xs">
                                            {coordinator.membership_no}
                                        </span>
                                    )}
                                </span>

                                {editable && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => remove(coordinator.ulid)}
                                        aria-label={t('common.actions.delete')}
                                    >
                                        <Trash2
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {editable &&
                    (candidates.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {t('admin.batches.no_candidates')}
                        </p>
                    ) : (
                        <form onSubmit={submit} className="space-y-2">
                            <Label htmlFor="member_id">
                                {t('admin.batches.coordinator_add')}
                            </Label>

                            <div className="flex flex-wrap gap-2">
                                <Select
                                    value={form.data.member_id}
                                    onValueChange={(value) =>
                                        form.setData('member_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="member_id"
                                        className="min-w-52 flex-1"
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'admin.batches.coordinator_add',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {candidates.map((candidate) => (
                                            <SelectItem
                                                key={candidate.value}
                                                value={candidate.value}
                                            >
                                                {candidate.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Button
                                    type="submit"
                                    disabled={
                                        form.data.member_id === '' ||
                                        form.processing
                                    }
                                >
                                    <UserPlus
                                        className="me-1 size-4"
                                        aria-hidden="true"
                                    />
                                    {t('common.actions.save')}
                                </Button>
                            </div>

                            <InputError message={form.errors.member_id} />
                        </form>
                    ))}
            </CardContent>
        </Card>
    );
}
