import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, MapPin, Plus, Ticket, Trash2 } from 'lucide-react';
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
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="mb-6 -ml-2 text-slate-300 hover:bg-white/10 hover:text-white rounded-full transition"
                    >
                        <Link href="/events">
                            <ArrowLeft
                                className="me-1.5 size-4 rtl:rotate-180"
                                aria-hidden="true"
                            />
                            {t('public.events.title')}
                        </Link>
                    </Button>

                    <div className="flex flex-wrap items-center gap-2">
                        <span className="inline-flex items-center rounded-full border border-teal-400/30 bg-teal-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                            {event.type_label}
                        </span>
                        {event.is_flagship && (
                            <span className="inline-flex items-center rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-300 backdrop-blur-md">
                                {t('jubilee.title')}
                            </span>
                        )}
                    </div>

                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {event.title}
                    </h1>

                    {event.summary && (
                        <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                            {event.summary}
                        </p>
                    )}

                    {/* THE DATE RULE */}
                    <div className="mt-6 flex flex-wrap items-center gap-4 text-sm text-slate-200">
                        <EventDate event={event} className="font-medium text-teal-300" />

                        {event.venue && (
                            <span className="flex items-center gap-1.5 text-slate-300">
                                <MapPin
                                    className="size-4 shrink-0 text-cyan-400"
                                    aria-hidden="true"
                                />
                                <span>{event.venue}</span>
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 sm:px-6 lg:px-8 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {event.description && (
                            <div className="glass-panel-light rounded-3xl p-6 sm:p-8 border border-teal-500/15 shadow-sm">
                                <div className="prose prose-slate max-w-none">
                                    <p className="leading-relaxed text-slate-700 whitespace-pre-line text-base">
                                        {event.description}
                                    </p>
                                </div>
                            </div>
                        )}

                        {event.address && (
                            <div className="rounded-3xl border border-teal-500/15 bg-white/90 p-6 shadow-sm backdrop-blur-md">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-900">
                                            {event.venue ?? t('public.events.venue_tba')}
                                        </h2>
                                        <p className="mt-1 text-sm text-slate-600">
                                            {event.address}
                                        </p>
                                    </div>
                                    {event.map_url && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="shrink-0 border-teal-500/20 text-teal-700 hover:bg-teal-50 rounded-xl"
                                        >
                                            <a
                                                href={event.map_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1.5"
                                            >
                                                <span>View Map</span>
                                                <ExternalLink className="size-3.5" />
                                            </a>
                                        </Button>
                                    )}
                                </div>
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
            <div className="rounded-3xl border border-teal-500/15 bg-white/90 p-6 shadow-sm backdrop-blur-md">
                <p className="text-center text-sm font-medium text-slate-500">
                    {t('public.events.registration_closed')}
                </p>
            </div>
        );
    }

    if (!isAuthenticated) {
        return (
            <div className="rounded-3xl border border-teal-500/20 bg-white/95 p-6 shadow-lg backdrop-blur-md">
                <div className="flex size-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                    <Ticket className="size-5" />
                </div>
                <h3 className="mt-3 text-base font-bold text-slate-900">
                    Member Registration
                </h3>
                <p className="mt-1 text-sm text-slate-600 leading-relaxed">
                    {t('public.events.members_only')}
                </p>
                <div className="mt-5 space-y-2.5">
                    <Button
                        asChild
                        className="w-full bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 text-white font-semibold shadow-md shadow-teal-500/20 hover:from-teal-600 hover:to-cyan-700 rounded-xl"
                    >
                        <Link href="/login">{t('common.actions.login')}</Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="w-full border-teal-500/25 text-teal-700 hover:bg-teal-50 rounded-xl"
                    >
                        <Link href="/join">{t('public.nav.join')}</Link>
                    </Button>
                </div>
            </div>
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
            <div className="rounded-3xl border border-teal-500/20 bg-white/95 p-6 shadow-xl shadow-teal-900/5 backdrop-blur-md">
                <div className="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                    <div className="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <Ticket className="size-4" aria-hidden="true" />
                    </div>
                    <h3 className="text-base font-bold text-slate-900">
                        {t('public.events.register')}
                    </h3>
                </div>

                <div className="mt-5 space-y-4">
                    {full && (
                        <div className="rounded-xl border border-amber-300 bg-amber-50 p-3 text-xs font-medium text-amber-900">
                            {t('public.events.registration_full')}
                        </div>
                    )}

                    {tickets.length > 0 && (
                        <div className="space-y-1.5">
                            <Label htmlFor="ticket_type_id" className="text-xs font-semibold text-slate-700">
                                {t('member.events.ticket_type')}
                            </Label>
                            <Select
                                value={form.data.ticket_type_id}
                                onValueChange={(value) =>
                                    form.setData('ticket_type_id', value)
                                }
                            >
                                <SelectTrigger id="ticket_type_id" className="rounded-xl border-slate-200 bg-white">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="rounded-xl">
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
                            <Label className="text-xs font-semibold text-slate-700">
                                {t('member.events.guests')}
                            </Label>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                className="h-7 text-xs text-teal-700 hover:bg-teal-50 hover:text-teal-800"
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
                                    className="me-1 size-3"
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
                                    className="rounded-xl border-slate-200"
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
                                    className="shrink-0 text-slate-400 hover:text-red-600 rounded-xl"
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
                        <Label htmlFor="notes" className="text-xs font-semibold text-slate-700">
                            {t('member.events.notes')}
                        </Label>
                        <Input
                            id="notes"
                            value={form.data.notes}
                            className="rounded-xl border-slate-200"
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <div className="space-y-1.5 rounded-2xl bg-slate-50 p-4 text-xs">
                        <div className="flex justify-between text-slate-500">
                            <span>{t('admin.events.seats')}</span>
                            <span className="font-semibold text-slate-800">
                                {choice('member.events.seats', seats, {
                                    count: seats,
                                })}
                            </span>
                        </div>
                        <div className="flex justify-between border-t border-slate-200/80 pt-1.5 text-sm font-bold text-slate-900">
                            <span>{t('member.events.amount_due')}</span>
                            <span className="text-teal-700">
                                {unitPrice === 0
                                    ? t('public.events.free')
                                    : formatCurrency(unitPrice * seats)}
                            </span>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        className="w-full bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 font-bold text-white shadow-md shadow-teal-500/20 hover:from-teal-600 hover:to-cyan-700 rounded-xl py-2.5 transition"
                        disabled={form.processing}
                    >
                        {t('public.events.register')}
                    </Button>
                </div>
            </div>
        </form>
    );
}
