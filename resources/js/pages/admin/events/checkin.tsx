import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ScanLine,
    Search,
    XCircle,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminEvent,
    CheckinStats,
    RecentCheckin,
    Registration,
} from '@/types/event';

type Scanned =
    | { found: false }
    | {
          found: true;
          registration: Registration;
          already: boolean;
          admissible: boolean;
      };

type Match = Registration & { admissible: boolean; already: boolean };

type Props = {
    event: AdminEvent;
    stats: CheckinStats;
    recent: RecentCheckin[];
    scanned?: Scanned;
    search?: { q: string | null };
    matches?: Match[];
};

/**
 * The gate.
 *
 * Large tap targets and a single decision on screen: this is used one-handed,
 * standing, with a queue waiting. Scanning SHOWS who is in front of the
 * operator; admitting them is the button — a camera pointed at a wall of
 * passes would otherwise admit all of them.
 *
 * @see docs/17-golden-jubilee.md section 5
 */
export default function Checkin({
    event,
    stats,
    recent,
    scanned,
    search,
    matches,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.checkin.title')}>
            <div className="mx-auto max-w-2xl space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href={`/admin/events/${event.ulid}`}>
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {event.title}
                    </Link>
                </Button>

                <div className="grid grid-cols-3 gap-3">
                    <Stat
                        label={t('admin.checkin.expected')}
                        value={stats.expected}
                    />
                    <Stat
                        label={t('admin.checkin.checked_in')}
                        value={stats.checked_in}
                        emphasis
                    />
                    <Stat
                        label={t('admin.checkin.remaining')}
                        value={stats.remaining}
                    />
                </div>

                {scanned && <ScanResult event={event} scanned={scanned} />}

                <Card>
                    <CardContent className="space-y-3 p-5">
                        <p className="flex items-center gap-2 font-medium">
                            <ScanLine className="size-4" aria-hidden="true" />
                            {t('admin.checkin.title')}
                        </p>
                        <p className="text-muted-foreground text-sm">
                            {t('admin.checkin.scan_help')}
                        </p>

                        <NameSearch event={event} initial={search?.q ?? ''} />

                        {(matches ?? []).length > 0 && (
                            <ul className="divide-y rounded-md border">
                                {(matches ?? []).map((match) => (
                                    <li
                                        key={match.ulid}
                                        className="flex flex-wrap items-center justify-between gap-2 p-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {match.registrant_name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {match.status_label}
                                                {match.registrant_phone
                                                    ? ` · ${match.registrant_phone}`
                                                    : ''}
                                            </p>
                                        </div>

                                        {match.already ? (
                                            <Badge variant="outline">
                                                {t('admin.checkin.checked_in')}
                                            </Badge>
                                        ) : (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                disabled={!match.admissible}
                                                onClick={() =>
                                                    router.get(
                                                        `/admin/events/${event.ulid}/checkin/${match.ulid}`,
                                                    )
                                                }
                                            >
                                                {t('common.actions.view')}
                                            </Button>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {recent.length > 0 && (
                    <Card>
                        <CardContent className="p-5">
                            <p className="font-medium">
                                {t('admin.checkin.recent')}
                            </p>
                            <ul className="mt-3 divide-y text-sm">
                                {recent.map((entry) => (
                                    <li
                                        key={`${entry.name}-${entry.checked_in_at}`}
                                        className="flex flex-wrap justify-between gap-2 py-2"
                                    >
                                        <span className="font-medium">
                                            {entry.name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatDateTime(
                                                entry.checked_in_at,
                                            )}
                                            {entry.operator
                                                ? ` · ${entry.operator}`
                                                : ''}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

/**
 * Finding someone by name, for when the pass is unavailable — a dead phone, a
 * screenshot of the wrong event, a pass forwarded to a relative. Selecting a
 * match opens the same scan screen the QR would have.
 */
function NameSearch({
    event,
    initial,
}: {
    event: AdminEvent;
    initial: string;
}) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        initial,
        `/admin/events/${event.ulid}/checkin`,
    );

    return (
        <div className="relative">
            <Search
                className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                aria-hidden="true"
            />
            <Input
                value={term}
                onChange={(e) => setTerm(e.target.value)}
                placeholder={t('admin.checkin.search_placeholder')}
                className="h-12 ps-9 text-base"
                aria-label={t('common.actions.search')}
            />
        </div>
    );
}

function Stat({
    label,
    value,
    emphasis = false,
}: {
    label: string;
    value: number;
    emphasis?: boolean;
}) {
    return (
        <div className="bg-card rounded-lg border p-4 text-center">
            <div
                className={
                    emphasis
                        ? 'text-brand-green-800 text-3xl font-semibold tabular-nums'
                        : 'text-3xl font-semibold tabular-nums'
                }
            >
                {formatNumber(value)}
            </div>
            <div className="text-muted-foreground mt-1 text-xs">{label}</div>
        </div>
    );
}

function ScanResult({
    event,
    scanned,
}: {
    event: AdminEvent;
    scanned: Scanned;
}) {
    const { t, choice } = useTranslation();

    if (!scanned.found) {
        return (
            <Card className="border-destructive">
                <CardContent className="flex items-center gap-3 p-5">
                    <XCircle
                        className="text-destructive size-6 shrink-0"
                        aria-hidden="true"
                    />
                    <p className="font-medium">
                        {t('admin.checkin.not_found')}
                    </p>
                </CardContent>
            </Card>
        );
    }

    const { registration, already, admissible } = scanned;

    return (
        <Card
            className={
                already
                    ? 'border-brand-gold-600'
                    : admissible
                      ? 'border-brand-green-600'
                      : 'border-destructive'
            }
        >
            <CardContent className="space-y-4 p-5">
                <div>
                    <p className="text-xl font-semibold">
                        {registration.registrant_name}
                    </p>
                    <p className="text-muted-foreground text-sm">
                        {choice('member.events.seats', registration.seats, {
                            count: registration.seats,
                        })}
                        {registration.ticket_type
                            ? ` · ${registration.ticket_type}`
                            : ''}
                    </p>
                    <Badge className="mt-2" variant="secondary">
                        {registration.status_label}
                    </Badge>
                </div>

                {already ? (
                    <p className="bg-brand-gold-100 text-brand-ink flex items-start gap-2 rounded-md p-3 text-sm">
                        <CheckCircle2
                            className="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span>
                            {t('admin.checkin.already', {
                                name: registration.registrant_name,
                                time: formatDateTime(
                                    registration.checkin?.checked_in_at,
                                ),
                                operator: registration.checkin?.operator ?? '',
                            })}
                        </span>
                    </p>
                ) : admissible ? (
                    <AdmitForm event={event} registration={registration} />
                ) : (
                    <p className="text-destructive text-sm">
                        {t(
                            registration.status === 'waitlisted'
                                ? 'admin.checkin.refused_waitlisted'
                                : 'admin.checkin.refused_cancelled',
                        )}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function AdmitForm({
    event,
    registration,
}: {
    event: AdminEvent;
    registration: Registration;
}) {
    const { t } = useTranslation();

    const form = useForm({ gate: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post(`/admin/events/${event.ulid}/checkin/${registration.ulid}`, {
            preserveScroll: true,
            onSuccess: () => router.get(`/admin/events/${event.ulid}/checkin`),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="space-y-1.5">
                <Label htmlFor="gate">{t('admin.checkin.gate')}</Label>
                <Input
                    id="gate"
                    value={form.data.gate}
                    onChange={(e) => form.setData('gate', e.target.value)}
                />
            </div>

            {/* Deliberately large: one-handed, standing, in a queue. */}
            <Button
                type="submit"
                size="lg"
                className="h-14 w-full text-base"
                disabled={form.processing}
            >
                <CheckCircle2 className="me-2 size-5" aria-hidden="true" />
                {t('admin.checkin.admit')}
            </Button>
        </form>
    );
}
