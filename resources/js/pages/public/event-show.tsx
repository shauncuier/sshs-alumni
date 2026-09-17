import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, MapPin, Plus, Ticket, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EventDate } from '@/components/public/event-date';
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
import { formatCurrency } from '@/lib/format';
import PublicLayout from '@/layouts/public-layout';
import type { PublicEvent, TicketType } from '@/types/event';

type Props = {
    event: PublicEvent;
    registration: { open: boolean; full: boolean };
};

export default function EventShow({ event, registration }: Props) {
    const { t } = useTranslation();
    const page = usePage();

    const auth = page.props.auth as { user?: unknown } | undefined;
    const isAuthenticated = Boolean(auth?.user);

    return (
        <PublicLayout
            title={event.title}
            description={event.summary ?? undefined}
        >
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-4xl px-4 py-12">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="text-white/70 hover:bg-white/10 hover:text-white"
                    >
                        <Link href="/events">
                            <ArrowLeft
                                className="me-1 size-4"
                                aria-hidden="true"
                            />
                            {t('public.events.title')}
                        </Link>
                    </Button>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{event.type_label}</Badge>
                        {event.is_flagship && (
                            <Badge className="bg-brand-gold-600 text-white">
                                {t('jubilee.title')}
                            </Badge>
                        )}
                    </div>

                    <h1 className="mt-3 text-3xl font-semibold">
                        {event.title}
                    </h1>

                    {event.summary && (
                        <p className="mt-3 max-w-2xl text-white/70">
                            {event.summary}
                        </p>
                    )}

                    {/* THE DATE RULE. */}
                    <EventDate event={event} className="mt-6 text-white/90" />

                    {event.venue && (
                        <p className="mt-2 flex items-center gap-2 text-white/70">
                            <MapPin
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span>{event.venue}</span>
                        </p>
                    )}
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto grid max-w-4xl gap-8 px-4 py-10 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {event.description && (
                            <div className="prose-sm max-w-none">
                                <p className="text-muted-foreground leading-relaxed whitespace-pre-line">
                                    {event.description}
                                </p>
                            </div>
                        )}

                        {event.address && (
                            <div>
                                <h2 className="font-semibold">
                                    {event.venue ??
                                        t('public.events.venue_tba')}
                                </h2>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {event.address}
                                </p>
                                {event.map_url && (
                                    <Button
                                        asChild
                                        variant="link"
                                        size="sm"
                                        className="px-0"
                                    >
                                        <a
                                            href={event.map_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {t('public.events.details')}
                                        </a>
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="lg:col-span-1">
                        <RegistrationPanel
                            event={event}
                            registration={registration}
                            isAuthenticated={isAuthenticated}
                        />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

function RegistrationPanel({
    event,
    registration,
    isAuthenticated,
}: {
    event: PublicEvent;
    registration: { open: boolean; full: boolean };
    isAuthenticated: boolean;
}) {
    const { t } = useTranslation();

    if (!event.registration_required) {
        return null;
    }

    if (!registration.open) {
        return (
            <Card>
                <CardContent className="p-5">
                    <p className="text-muted-foreground text-sm">
                        {t('public.events.registration_closed')}
                    </p>
                </CardContent>
            </Card>
        );
    }

    if (!isAuthenticated) {
        return (
            <Card>
                <CardContent className="space-y-3 p-5">
                    <p className="text-muted-foreground text-sm">
                        {t('public.events.members_only')}
                    </p>
                    <Button asChild className="w-full">
                        <Link href="/login">{t('common.actions.login')}</Link>
                    </Button>
                    <Button asChild variant="outline" className="w-full">
                        <Link href="/join">{t('public.nav.join')}</Link>
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return <RegistrationForm event={event} full={registration.full} />;
}

/**
 * Registering, with guests.
 *
 * A full event still accepts registrations — onto the waitlist. Turning an
 * alumnus away automatically is the wrong default for a reunion, so the form
 * says what will happen rather than disabling itself.
 */
function RegistrationForm({
    event,
    full,
}: {
    event: PublicEvent;
    full: boolean;
}) {
    const { t, choice } = useTranslation();
    const [guests, setGuests] = useState<
        Array<{ name: string; relation: string; age_group: string }>
    >([]);

    const tickets = event.ticket_types ?? [];

    const form = useForm<{
        ticket_type_id: string;
        guests: Array<{ name: string; relation: string; age_group: string }>;
        notes: string;
    }>({
        ticket_type_id: tickets[0] ? String(tickets[0].id) : '',
        guests: [],
        notes: '',
    });

    const submit = (event_: FormEvent) => {
        event_.preventDefault();

        form.transform((data) => ({
            ...data,
            guests,
            ticket_type_id: data.ticket_type_id || undefined,
        }));
        form.post(`/events/${event.slug}/register`, { preserveScroll: true });
    };

    const selected = tickets.find(
        (ticket) => String(ticket.id) === form.data.ticket_type_id,
    );

    const unitPrice = selected?.price ?? event.registration_fee ?? 0;
    const seats = 1 + guests.length;

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Ticket className="size-4" aria-hidden="true" />
                        {t('public.events.register')}
                    </CardTitle>
                </CardHeader>

                <CardContent className="space-y-4">
                    {full && (
                        <p className="bg-brand-gold-100 text-brand-ink rounded-md p-3 text-sm">
                            {t('public.events.registration_full')}
                        </p>
                    )}

                    {tickets.length > 0 && (
                        <div className="space-y-1.5">
                            <Label htmlFor="ticket_type_id">
                                {t('member.events.ticket_type')}
                            </Label>
                            <Select
                                value={form.data.ticket_type_id}
                                onValueChange={(value) =>
                                    form.setData('ticket_type_id', value)
                                }
                            >
                                <SelectTrigger id="ticket_type_id">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {tickets.map((ticket: TicketType) => (
                                        <SelectItem
                                            key={ticket.id}
                                            value={String(ticket.id)}
                                            disabled={!ticket.available}
                                        >
                                            {ticket.name} —{' '}
                                            {ticket.price === 0
                                                ? t('public.events.free')
                                                : formatCurrency(ticket.price)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.ticket_type_id} />
                        </div>
                    )}

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label>{t('member.events.guests')}</Label>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() =>
                                    setGuests((current) => [
                                        ...current,
                                        {
                                            name: '',
                                            relation: '',
                                            age_group: '',
                                        },
                                    ])
                                }
                            >
                                <Plus
                                    className="me-1 size-3.5"
                                    aria-hidden="true"
                                />
                                {t('member.events.add_guest')}
                            </Button>
                        </div>

                        {guests.map((guest, index) => (
                            <div key={index} className="flex gap-2">
                                <Input
                                    value={guest.name}
                                    placeholder={t('member.events.guest_name')}
                                    onChange={(e) =>
                                        setGuests((current) =>
                                            current.map((one, i) =>
                                                i === index
                                                    ? {
                                                          ...one,
                                                          name: e.target.value,
                                                      }
                                                    : one,
                                            ),
                                        )
                                    }
                                    required
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label={t('member.events.remove_guest')}
                                    onClick={() =>
                                        setGuests((current) =>
                                            current.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="notes">
                            {t('member.events.notes')}
                        </Label>
                        <Input
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <dl className="space-y-1 border-t pt-4 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-muted-foreground">
                                {t('admin.events.seats')}
                            </dt>
                            <dd>
                                {choice('member.events.seats', seats, {
                                    count: seats,
                                })}
                            </dd>
                        </div>
                        <div className="flex justify-between font-medium">
                            <dt>{t('member.events.amount_due')}</dt>
                            <dd>
                                {unitPrice === 0
                                    ? t('public.events.free')
                                    : formatCurrency(unitPrice * seats)}
                            </dd>
                        </div>
                    </dl>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={form.processing}
                    >
                        {t('public.events.register')}
                    </Button>
                </CardContent>
            </Card>
        </form>
    );
}
