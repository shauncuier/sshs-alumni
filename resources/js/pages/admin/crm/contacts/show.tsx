import { Deferred, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Link2, Link2Off, Plus, UserCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { CrmTimeline } from '@/components/admin/crm-timeline';
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
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Activity, Contact, CrmOptions, Task } from '@/types/crm';

type Props = {
    contact: Contact;
    timeline?: Activity[];
    tasks?: Task[];
    options: CrmOptions;
    can: { update: boolean; assign: boolean; delete: boolean };
};

export default function ContactShow({
    contact,
    timeline,
    tasks,
    options,
    can,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={contact.name}>
            <div className="space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/admin/crm/contacts">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('admin.crm.contacts')}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">{contact.name}</h1>
                    <Badge>{contact.pipeline_label}</Badge>
                    <Badge variant="secondary">{contact.type_label}</Badge>
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    {t('admin.crm.timeline')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {can.update && (
                                    <LogActivity contact={contact} />
                                )}

                                <Deferred
                                    data="timeline"
                                    fallback={<TimelineSkeleton />}
                                >
                                    <CrmTimeline
                                        activities={timeline ?? []}
                                        // Only worth distinguishing once the
                                        // two records are actually linked.
                                        showSource={Boolean(contact.member)}
                                    />
                                </Deferred>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    {t('admin.crm.tasks')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Deferred
                                    data="tasks"
                                    fallback={<TimelineSkeleton />}
                                >
                                    <TaskList tasks={tasks ?? []} />
                                </Deferred>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        <StageCard
                            contact={contact}
                            options={options}
                            editable={can.update}
                        />

                        <OwnerCard
                            contact={contact}
                            options={options}
                            editable={can.assign}
                        />

                        <MemberLinkCard
                            contact={contact}
                            editable={can.update}
                        />

                        <TagsCard
                            contact={contact}
                            options={options}
                            editable={can.update}
                        />

                        <DetailsCard contact={contact} />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

function StageCard({
    contact,
    options,
    editable,
}: {
    contact: Contact;
    options: CrmOptions;
    editable: boolean;
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('admin.crm.stage')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Select
                    value={contact.pipeline_status}
                    disabled={!editable}
                    onValueChange={(stage) =>
                        router.post(
                            `/admin/crm/contacts/${contact.ulid}/stage`,
                            { stage },
                            { preserveScroll: true },
                        )
                    }
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {options.stages.map((stage) => (
                            <SelectItem key={stage.value} value={stage.value}>
                                {stage.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </CardContent>
        </Card>
    );
}

function OwnerCard({
    contact,
    options,
    editable,
}: {
    contact: Contact;
    options: CrmOptions;
    editable: boolean;
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('admin.crm.owner')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Select
                    value={contact.owner ? String(contact.owner.id) : '__none'}
                    disabled={!editable}
                    onValueChange={(value) =>
                        router.post(
                            `/admin/crm/contacts/${contact.ulid}/owner`,
                            { owner_id: value === '__none' ? null : value },
                            { preserveScroll: true },
                        )
                    }
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__none">
                            {t('admin.crm.owner_none')}
                        </SelectItem>
                        {options.owners.map((owner) => (
                            <SelectItem key={owner.value} value={owner.value}>
                                {owner.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </CardContent>
        </Card>
    );
}

/**
 * Linking a contact to the alumni record that is the same person.
 *
 * Once linked, the timeline above becomes one feed across both records — which
 * is the entire reason this exists.
 */
function MemberLinkCard({
    contact,
    editable,
}: {
    contact: Contact;
    editable: boolean;
}) {
    const { t } = useTranslation();

    const form = useForm({ member_ulid: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post(`/admin/crm/contacts/${contact.ulid}/link`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <Link2 className="size-4" aria-hidden="true" />
                    {t('admin.crm.member')}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-3">
                {contact.member ? (
                    <>
                        <Link
                            href={`/admin/members/${contact.member.ulid}`}
                            className="hover:bg-accent flex items-center gap-2 rounded-md border p-3"
                        >
                            <UserCheck
                                className="text-brand-green-700 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span className="min-w-0">
                                <span className="block truncate font-medium">
                                    {contact.member.full_name}
                                </span>
                                <span className="tabular-id text-muted-foreground block truncate text-xs">
                                    {contact.member.membership_no ??
                                        contact.member.status_label}
                                </span>
                            </span>
                        </Link>

                        {editable && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            t('admin.crm.unlink_confirm'),
                                        )
                                    ) {
                                        router.delete(
                                            `/admin/crm/contacts/${contact.ulid}/link`,
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                            >
                                <Link2Off
                                    className="me-1 size-4"
                                    aria-hidden="true"
                                />
                                {t('admin.crm.unlink')}
                            </Button>
                        )}
                    </>
                ) : (
                    editable && (
                        <form onSubmit={submit} className="space-y-2">
                            <p className="text-muted-foreground text-sm">
                                {t('admin.crm.link_hint')}
                            </p>
                            <Label htmlFor="member_ulid" className="sr-only">
                                {t('admin.crm.link')}
                            </Label>
                            <Input
                                id="member_ulid"
                                value={form.data.member_ulid}
                                placeholder={t('admin.crm.member_search')}
                                onChange={(e) =>
                                    form.setData('member_ulid', e.target.value)
                                }
                            />
                            <InputError message={form.errors.member_ulid} />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={
                                    form.data.member_ulid === '' ||
                                    form.processing
                                }
                            >
                                {t('admin.crm.link')}
                            </Button>
                        </form>
                    )
                )}
            </CardContent>
        </Card>
    );
}

function TagsCard({
    contact,
    options,
    editable,
}: {
    contact: Contact;
    options: CrmOptions;
    editable: boolean;
}) {
    const { t } = useTranslation();

    const applied = new Set((contact.tags ?? []).map((tag) => tag.id));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('admin.crm.tags')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {options.tags.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {t('admin.crm.tags_empty')}
                    </p>
                ) : (
                    <div className="flex flex-wrap gap-2">
                        {options.tags.map((tag) => {
                            const on = applied.has(tag.id);

                            return (
                                <button
                                    key={tag.id}
                                    type="button"
                                    disabled={!editable}
                                    onClick={() =>
                                        router.post(
                                            `/admin/crm/contacts/${contact.ulid}/tags`,
                                            { tag_id: tag.id },
                                            { preserveScroll: true },
                                        )
                                    }
                                    className={
                                        on
                                            ? 'rounded-full border px-3 py-1 text-xs font-medium'
                                            : 'text-muted-foreground hover:bg-accent rounded-full border px-3 py-1 text-xs'
                                    }
                                    style={
                                        on && tag.color
                                            ? {
                                                  borderColor: tag.color,
                                                  color: tag.color,
                                              }
                                            : undefined
                                    }
                                >
                                    {tag.name}
                                </button>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function DetailsCard({ contact }: { contact: Contact }) {
    const { t } = useTranslation();

    const rows: Array<[string, string | null]> = [
        [t('common.labels.email'), contact.email],
        [t('common.labels.phone'), contact.phone],
        ['WhatsApp', contact.whatsapp],
        [t('admin.crm.details'), contact.organization_name],
        [t('public.join.fields.city'), contact.city],
        [t('public.join.fields.country'), contact.country],
        [t('common.labels.date'), formatDate(contact.created_at)],
    ];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('admin.crm.details')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <dl className="space-y-2 text-sm">
                    {rows
                        .filter(([, value]) => Boolean(value))
                        .map(([label, value]) => (
                            <div
                                key={label}
                                className="flex justify-between gap-3"
                            >
                                <dt className="text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="min-w-0 truncate text-end">
                                    {value}
                                </dd>
                            </div>
                        ))}
                </dl>

                {contact.notes && (
                    <p className="text-muted-foreground mt-4 border-t pt-4 text-sm whitespace-pre-line">
                        {contact.notes}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Recording a call, an email, a meeting or a note.
 *
 * `system` is absent from the type list on purpose — those rows are written by
 * services when something actually happened, and a form that could post one
 * would let anybody fabricate a history the platform appears to vouch for.
 */
function LogActivity({ contact }: { contact: Contact }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        type: 'call',
        subject_line: '',
        body: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post(`/admin/crm/contacts/${contact.ulid}/activities`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" variant="outline" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.crm.log_activity')}
            </Button>
        );
    }

    return (
        <form onSubmit={submit} className="space-y-3 rounded-md border p-4">
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <Label htmlFor="activity_type">
                        {t('common.labels.status')}
                    </Label>
                    <Select
                        value={form.data.type}
                        onValueChange={(value) => form.setData('type', value)}
                    >
                        <SelectTrigger id="activity_type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {['call', 'email', 'meeting', 'note'].map((one) => (
                                <SelectItem key={one} value={one}>
                                    {t(`enums.crm_activity_type.${one}`)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.type} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="subject_line">
                        {t('common.labels.name')}
                    </Label>
                    <Input
                        id="subject_line"
                        value={form.data.subject_line}
                        onChange={(e) =>
                            form.setData('subject_line', e.target.value)
                        }
                    />
                    <InputError message={form.errors.subject_line} />
                </div>
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="body">{t('admin.crm.activity_body')}</Label>
                <textarea
                    id="body"
                    rows={3}
                    value={form.data.body}
                    onChange={(e) => form.setData('body', e.target.value)}
                    className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                />
                <InputError message={form.errors.body} />
            </div>

            <div className="flex gap-2">
                <Button type="submit" size="sm" disabled={form.processing}>
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
    );
}

function TaskList({ tasks }: { tasks: Task[] }) {
    const { t } = useTranslation();

    if (tasks.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                {t('admin.crm.tasks_empty')}
            </p>
        );
    }

    return (
        <ul className="divide-y">
            {tasks.map((task) => (
                <li
                    key={task.id}
                    className="flex flex-wrap items-center justify-between gap-2 py-3"
                >
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium">
                            {task.title}
                        </p>
                        <p className="text-muted-foreground text-xs">
                            {task.due_at ? formatDate(task.due_at) : '—'}
                            {task.assignee ? ` · ${task.assignee.name}` : ''}
                        </p>
                    </div>

                    {task.is_overdue ? (
                        <Badge variant="destructive">
                            {t('admin.crm.task_overdue')}
                        </Badge>
                    ) : (
                        <Badge variant="outline">{task.status_label}</Badge>
                    )}
                </li>
            ))}
        </ul>
    );
}

function TimelineSkeleton() {
    return (
        <div className="space-y-3">
            <Skeleton className="h-4 w-2/3" />
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-4 w-3/4" />
        </div>
    );
}
