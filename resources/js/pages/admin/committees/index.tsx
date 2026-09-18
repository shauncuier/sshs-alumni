import { router, useForm } from '@inertiajs/react';
import { Plus, UserMinus, UserSquare } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
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
import AdminLayout from '@/layouts/admin-layout';
import type { Committee, Option } from '@/types/money';

type Props = {
    committees: Committee[];
    options: { types: Option[]; member_statuses: Option[] };
    can: { manage: boolean };
};

export default function CommitteesIndex({ committees, options, can }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.committees.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.committees.title')}
                    </h1>

                    {can.manage && <AddCommitteeForm options={options} />}
                </div>

                {committees.length === 0 ? (
                    <EmptyState
                        icon={UserSquare}
                        title={t('common.states.empty')}
                        description={t('admin.committees.empty')}
                    />
                ) : (
                    <div className="space-y-5">
                        {committees.map((committee) => (
                            <CommitteeCard
                                key={committee.id}
                                committee={committee}
                                can={can}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function CommitteeCard({
    committee,
    can,
}: {
    committee: Committee;
    can: { manage: boolean };
}) {
    const { t } = useTranslation();
    const getInitials = useInitials();
    const [adding, setAdding] = useState(false);

    const form = useForm({ name: '', role: '', designation: '' });

    const serving = committee.members.filter((one) => one.status === 'active');
    const past = committee.members.filter((one) => one.status !== 'active');

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center justify-between gap-2 text-base">
                    <span className="flex flex-wrap items-center gap-2">
                        {committee.name}
                        <Badge variant="secondary">
                            {committee.type_label}
                        </Badge>
                        {committee.term_start && (
                            <span className="text-muted-foreground text-xs font-normal tabular-nums">
                                {committee.term_start.slice(0, 4)}
                                {committee.term_end
                                    ? ` — ${committee.term_end.slice(0, 4)}`
                                    : ''}
                            </span>
                        )}
                    </span>

                    {can.manage && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setAdding((on) => !on)}
                        >
                            <Plus
                                className="me-1 size-3.5"
                                aria-hidden="true"
                            />
                            {t('admin.committees.add_member')}
                        </Button>
                    )}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {committee.description && (
                    <p className="text-muted-foreground text-sm">
                        {committee.description}
                    </p>
                )}

                {serving.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {t('common.states.empty')}
                    </p>
                ) : (
                    <ul className="divide-y">
                        {serving.map((person) => (
                            <li
                                key={person.id}
                                className="flex items-center gap-3 py-2"
                            >
                                <Avatar className="size-8 shrink-0">
                                    {person.photo_url && (
                                        <AvatarImage
                                            src={person.photo_url}
                                            alt=""
                                        />
                                    )}
                                    <AvatarFallback className="text-xs">
                                        {getInitials(person.name)}
                                    </AvatarFallback>
                                </Avatar>

                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {person.name}
                                    </span>
                                    <span className="text-muted-foreground block truncate text-xs">
                                        {person.role}
                                        {person.designation
                                            ? ` · ${person.designation}`
                                            : ''}
                                    </span>
                                </span>

                                {can.manage && (
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={t(
                                            'admin.committees.remove_member',
                                        )}
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    t(
                                                        'admin.committees.remove_confirm',
                                                    ),
                                                )
                                            ) {
                                                router.delete(
                                                    `/admin/committees/${committee.id}/members/${person.id}`,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <UserMinus
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {/* The record of who served, kept rather than deleted. */}
                {past.length > 0 && (
                    <details className="text-sm">
                        <summary className="text-muted-foreground cursor-pointer">
                            {t('admin.committees.past')}
                        </summary>
                        <ul className="text-muted-foreground mt-2 space-y-1">
                            {past.map((person) => (
                                <li key={person.id}>
                                    {person.name} · {person.role}
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                {adding && (
                    <form
                        onSubmit={(e: FormEvent) => {
                            e.preventDefault();
                            form.post(
                                `/admin/committees/${committee.id}/members`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        form.reset();
                                        setAdding(false);
                                    },
                                },
                            );
                        }}
                        className="bg-muted/40 flex flex-wrap gap-2 rounded-md p-3"
                    >
                        <Input
                            value={form.data.name}
                            placeholder={t('common.labels.name')}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            className="min-w-44 flex-1"
                            required
                        />
                        <Input
                            value={form.data.role}
                            placeholder={t('admin.committees.role')}
                            onChange={(e) =>
                                form.setData('role', e.target.value)
                            }
                            className="min-w-40 flex-1"
                            required
                        />
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                        <InputError message={form.errors.name} />
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function AddCommitteeForm({ options }: { options: { types: Option[] } }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: '',
        type: options.types[0]?.value ?? 'executive',
        term_start: '',
        term_end: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/committees', {
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
                {t('admin.committees.add')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-4">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="committee_name">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="committee_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="committee_type">
                                {t('common.labels.status')}
                            </Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) =>
                                    form.setData('type', value)
                                }
                            >
                                <SelectTrigger id="committee_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.types.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="term_start">
                                {t('admin.committees.term_start')}
                            </Label>
                            <Input
                                id="term_start"
                                type="date"
                                value={form.data.term_start}
                                onChange={(e) =>
                                    form.setData('term_start', e.target.value)
                                }
                            />
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
