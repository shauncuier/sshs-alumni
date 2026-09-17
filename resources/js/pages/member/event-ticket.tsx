import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, MapPin, Printer } from 'lucide-react';
import { EventDate } from '@/components/public/event-date';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDateTime } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { Registration } from '@/types/event';

type Props = {
    registration: Registration;
    /** An inline SVG data URI, rendered server-side from the pass token. */
    qr: string;
};

/**
 * One event pass.
 *
 * The QR encodes a URL the SERVER resolves. Nothing about the member is in the
 * code itself, so a photographed pass discloses nothing beyond what the gate
 * already knows.
 */
export default function EventTicket({ registration, qr }: Props) {
    const { t, choice } = useTranslation();

    const event = registration.event;
    const checkedIn = registration.checkin != null;

    return (
        <MemberLayout title={event?.title ?? t('member.events.pass')}>
            <div className="mx-auto max-w-lg space-y-5">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="print:hidden"
                >
                    <Link href="/my/events">
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {t('member.events.title')}
                    </Link>
                </Button>

                <Card className="overflow-hidden">
                    <div className="bg-brand-green-900 p-5 text-white">
                        <h1 className="text-lg font-semibold">
                            {event?.title ?? registration.registrant_name}
                        </h1>

                        {event && (
                            <EventDate
                                event={event}
                                className="mt-2 text-sm text-white/80"
                            />
                        )}

                        {event?.venue && (
                            <p className="mt-1 flex items-center gap-2 text-sm text-white/70">
                                <MapPin
                                    className="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>{event.venue}</span>
                            </p>
                        )}
                    </div>

                    <CardContent className="space-y-5 p-6 text-center">
                        {checkedIn ? (
                            <Badge className="gap-1">
                                <CheckCircle2
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                                {t('member.events.checked_in', {
                                    time: formatDateTime(
                                        registration.checkin?.checked_in_at,
                                    ),
                                })}
                            </Badge>
                        ) : (
                            <Badge variant="secondary">
                                {registration.status_label}
                            </Badge>
                        )}

                        {/* A cancelled or waitlisted pass still shows its code —
                            the gate is what decides, and the member should not
                            be left guessing what they are holding. */}
                        <img
                            src={qr}
                            alt=""
                            className="mx-auto size-56"
                            width={224}
                            height={224}
                        />

                        <div>
                            <p className="font-medium">
                                {registration.registrant_name}
                            </p>
                            <p className="tabular-id text-muted-foreground text-xs">
                                {registration.ulid}
                            </p>
                        </div>

                        <p className="text-muted-foreground text-sm print:hidden">
                            {t('member.events.pass_help')}
                        </p>

                        <dl className="space-y-1.5 border-t pt-4 text-start text-sm">
                            {registration.ticket_type && (
                                <Row
                                    label={t('member.events.ticket_type')}
                                    value={registration.ticket_type}
                                />
                            )}
                            <Row
                                label={t('admin.events.seats')}
                                value={choice(
                                    'member.events.seats',
                                    registration.seats,
                                    { count: registration.seats },
                                )}
                            />
                            <Row
                                label={t('member.events.amount_due')}
                                value={
                                    registration.amount_due === 0
                                        ? t('public.events.free')
                                        : `${formatCurrency(registration.amount_due)} · ${registration.payment_status_label}`
                                }
                            />
                        </dl>

                        {(registration.guests ?? []).length > 0 && (
                            <div className="border-t pt-4 text-start">
                                <p className="text-sm font-medium">
                                    {t('member.events.guests')}
                                </p>
                                <ul className="text-muted-foreground mt-1 space-y-0.5 text-sm">
                                    {(registration.guests ?? []).map(
                                        (guest) => (
                                            <li key={guest.name}>
                                                {guest.name}
                                                {guest.relation
                                                    ? ` · ${guest.relation}`
                                                    : ''}
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2 print:hidden">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => window.print()}
                    >
                        <Printer className="me-1 size-4" aria-hidden="true" />
                        {t('member.events.print')}
                    </Button>

                    {/* A pass that has been through the gate is a record of
                        attendance, so cancelling is gone once it has. */}
                    {!checkedIn && registration.status !== 'cancelled' && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                if (
                                    window.confirm(
                                        t('member.events.cancel_confirm'),
                                    )
                                ) {
                                    router.delete(
                                        `/my/events/${registration.ulid}`,
                                    );
                                }
                            }}
                        >
                            {t('member.events.cancel')}
                        </Button>
                    )}
                </div>
            </div>
        </MemberLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-end font-medium">{value}</dd>
        </div>
    );
}
