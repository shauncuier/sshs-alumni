import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ScanLine, Sparkles, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { TicketTypes } from '@/components/admin/ticket-types';
import { EventDate } from '@/components/public/event-date';
import { Badge } from '@/components/ui/badge';
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
import AdminLayout from '@/layouts/admin-layout';
import type { AdminEvent, CheckinStats, Seats } from '@/types/event';
import type { Option } from '@/types/batch';

type Props = {
    event: AdminEvent;
    seats: Seats;
    checkins: CheckinStats;
    options: { statuses: Option[]; types: Option[] };
    can: {
        update: boolean;
        publish: boolean;
        checkin: boolean;
        delete: boolean;
    };
};

export default function EventShow({
    event,
    seats,
    checkins,
    options,
    can,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={event.title}>
            <div className="space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/admin/events">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('admin.nav.events')}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-center gap-3">
                    {event.is_flagship && (
                        <Sparkles
                            className="text-brand-gold-600 size-5"
                            aria-hidden="true"
                        />
                    )}
                    <h1 className="text-2xl font-semibold">{event.title}</h1>
                    <Badge variant="secondary">{event.status_label}</Badge>
                    <EventDate
                        event={event}
                        className="text-muted-foreground text-sm"
                    />
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={`/admin/events/${event.ulid}/registrations`}
                        >
                            <Users className="me-1 size-4" aria-hidden="true" />
                            {t('admin.events.registrations')} ·{' '}
                            {formatNumber(event.registrations_count ?? 0)}
                        </Link>
                    </Button>

                    {can.checkin && (
                        <Button asChild size="sm" variant="outline">
                            <Link href={`/admin/events/${event.ulid}/checkin`}>
                                <ScanLine
                                    className="me-1 size-4"
                                    aria-hidden="true"
                                />
                                {t('admin.events.checkin_link')}
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <Stat
                        label={t('admin.events.seats')}
                        value={
                            seats.capacity === null
                                ? t('admin.events.seats_unlimited')
                                : t('admin.events.seats_taken', {
                                      taken: formatNumber(seats.taken),
                                      capacity: formatNumber(seats.capacity),
                                  })
                        }
                    />
                    <Stat
                        label={t('admin.checkin.checked_in')}
                        value={formatNumber(checkins.checked_in)}
                    />
                    <Stat
                        label={t('admin.checkin.expected')}
                        value={formatNumber(checkins.expected)}
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <DateCard event={event} editable={can.publish} />
                    <StatusCard
                        event={event}
                        options={options}
                        editable={can.publish}
                    />
                </div>

                <TicketTypes event={event} editable={can.update} />

                <DetailsForm event={event} editable={can.update} />
            </div>
        </AdminLayout>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="bg-card rounded-lg border p-4">
            <div className="text-muted-foreground text-xs">{label}</div>
            <div className="mt-1 font-medium">{value}</div>
        </div>
    );
}

/**
 * THE DATE RULE, as a control.
 *
 * Setting a date here does not announce it. Announcing is a separate button
 * behind `events.publish`, and it can be undone — a date that has to be pulled
 * back is exactly when the "to be announced" line matters most.
 */
function DateCard({
    event,
    editable,
}: {
    event: AdminEvent;
    editable: boolean;
}) {
    const { t } = useTranslation();

    const form = useForm({
        announce: true,
        starts_at: event.starts_at ? event.starts_at.slice(0, 16) : '',
        ends_at: event.ends_at ? event.ends_at.slice(0, 16) : '',
    });

    const announce = (e: FormEvent) => {
        e.preventDefault();

        form.transform((data) => ({ ...data, announce: true }));
        form.post(`/admin/events/${event.ulid}/date`, { preserveScroll: true });
    };

    const retract = () => {
        if (!window.confirm(t('admin.events.date_retract_confirm'))) {
            return;
        }

        form.transform(() => ({ announce: false }));
        form.post(`/admin/events/${event.ulid}/date`, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between gap-2 text-base">
                    <span>{t('admin.events.date')}</span>
                    <Badge variant={event.date_is_tba ? 'outline' : 'default'}>
                        {event.date_is_tba
                            ? t('admin.events.date_tba')
                            : t('common.states.yes')}
                    </Badge>
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                <p className="text-muted-foreground text-sm">
                    {t('admin.events.date_draft_help')}
                </p>

                <form onSubmit={announce} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="starts_at">
                                {t('admin.events.starts_at')}
                            </Label>
                            <Input
                                id="starts_at"
                                type="datetime-local"
                                value={form.data.starts_at}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('starts_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.starts_at} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ends_at">
                                {t('admin.events.ends_at')}
                            </Label>
                            <Input
                                id="ends_at"
                                type="datetime-local"
                                value={form.data.ends_at}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('ends_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.ends_at} />
                        </div>
                    </div>

                    {editable && (
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={form.processing}>
                                {t('admin.events.date_announce')}
                            </Button>

                            {!event.date_is_tba && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={retract}
                                >
                                    {t('admin.events.date_retract')}
                                </Button>
                            )}
                        </div>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

function StatusCard({
    event,
    options,
    editable,
}: {
    event: AdminEvent;
    options: { statuses: Option[] };
    editable: boolean;
}) {
    const { t } = useTranslation();

    const form = useForm({ status: event.status });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(`/admin/events/${event.ulid}/status`, {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    {t('common.labels.status')}
                </CardTitle>
            </CardHeader>

            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <Select
                        value={form.data.status}
                        disabled={!editable}
                        onValueChange={(value) => form.setData('status', value)}
                    >
                        <SelectTrigger>
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

                    {editable && (
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

function DetailsForm({
    event,
    editable,
}: {
    event: AdminEvent;
    editable: boolean;
}) {
    const { t } = useTranslation();

    const form = useForm({
        type: event.type,
        title: event.title,
        summary: event.summary ?? '',
        description: event.description ?? '',
        starts_at: event.starts_at ? event.starts_at.slice(0, 16) : '',
        ends_at: event.ends_at ? event.ends_at.slice(0, 16) : '',
        venue: event.venue ?? '',
        address: event.address ?? '',
        map_url: event.map_url ?? '',
        registration_required: event.registration_required,
        registration_opens_at: event.registration_opens_at
            ? event.registration_opens_at.slice(0, 16)
            : '',
        registration_closes_at: event.registration_closes_at
            ? event.registration_closes_at.slice(0, 16)
            : '',
        capacity: event.capacity === null ? '' : String(event.capacity),
        registration_fee:
            event.registration_fee === null
                ? ''
                : String(event.registration_fee),
        currency: event.currency,
        organizer_name: event.organizer_name ?? '',
        contact_phone: event.contact_phone ?? '',
        contact_email: event.contact_email ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/events/${event.ulid}`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {t('admin.events.details')}
                    </CardTitle>
                </CardHeader>

                <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            id="title"
                            label={t('common.labels.name')}
                            error={form.errors.title}
                        >
                            <Input
                                id="title"
                                value={form.data.title}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            id="venue"
                            label={t('admin.events.venue')}
                            error={form.errors.venue}
                        >
                            <Input
                                id="venue"
                                value={form.data.venue}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('venue', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <Field
                        id="summary"
                        label={t('admin.events.summary')}
                        error={form.errors.summary}
                    >
                        <Input
                            id="summary"
                            value={form.data.summary}
                            disabled={!editable}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="description"
                        label={t('common.labels.description')}
                        error={form.errors.description}
                    >
                        <textarea
                            id="description"
                            rows={5}
                            value={form.data.description}
                            disabled={!editable}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none disabled:opacity-50"
                        />
                    </Field>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="registration_required"
                            checked={form.data.registration_required}
                            disabled={!editable}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'registration_required',
                                    checked === true,
                                )
                            }
                        />
                        <Label htmlFor="registration_required">
                            {t('public.events.register')}
                        </Label>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field
                            id="capacity"
                            label={t('admin.events.seats')}
                            error={form.errors.capacity}
                        >
                            <Input
                                id="capacity"
                                type="number"
                                inputMode="numeric"
                                placeholder={t('admin.events.seats_unlimited')}
                                value={form.data.capacity}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('capacity', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            id="registration_fee"
                            label={t('member.events.amount_due')}
                            error={form.errors.registration_fee}
                        >
                            <Input
                                id="registration_fee"
                                type="number"
                                step="0.01"
                                value={form.data.registration_fee}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData(
                                        'registration_fee',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>

                        <Field
                            id="currency"
                            label={t('admin.events.currency')}
                            error={form.errors.currency}
                        >
                            <Input
                                id="currency"
                                maxLength={3}
                                value={form.data.currency}
                                disabled={!editable}
                                onChange={(e) =>
                                    form.setData('currency', e.target.value)
                                }
                            />
                        </Field>
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

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
