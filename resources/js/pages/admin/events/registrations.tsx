import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowUpCircle,
    CheckCircle2,
    Plus,
    Search,
    X,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminEvent, Registration, Seats } from '@/types/event';
import type { Paginated } from '@/types/member';

type Props = {
    event: AdminEvent;
    registrations: Paginated<Registration>;
    filters: { q: string | null; status: string | null };
    seats: Seats;
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    confirmed: 'default',
    waitlisted: 'secondary',
    cancelled: 'outline',
};

export default function Registrations({
    event,
    registrations,
    filters,
    seats,
}: Props) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        `/admin/events/${event.ulid}/registrations`,
    );

    return (
        <AdminLayout title={t('admin.events.registrations')}>
            <div className="space-y-5">
                <Button asChild variant="ghost" size="sm">
                    <Link href={`/admin/events/${event.ulid}`}>
                        <ArrowLeft className="me-1 size-4" aria-hidden="true" />
                        {event.title}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.events.registrations')}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {seats.capacity === null
                            ? t('admin.events.seats_unlimited')
                            : t('admin.events.seats_taken', {
                                  taken: formatNumber(seats.taken),
                                  capacity: formatNumber(seats.capacity),
                              })}
                    </p>
                </div>

                <WalkInForm event={event} />

                <div className="flex flex-wrap gap-2">
                    <div className="relative min-w-60 flex-1">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder={t('admin.members.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    {filters.q && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    `/admin/events/${event.ulid}/registrations`,
                                )
                            }
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {registrations.data.length === 0 ? (
                    <EmptyState
                        title={t('common.states.no_results')}
                        description={t('admin.events.registrations_empty')}
                    />
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <Th>{t('common.labels.name')}</Th>
                                    <Th className="hidden sm:table-cell">
                                        {t('admin.events.seats')}
                                    </Th>
                                    <Th className="hidden md:table-cell">
                                        {t('member.events.amount_due')}
                                    </Th>
                                    <Th>{t('common.labels.status')}</Th>
                                    <Th className="hidden lg:table-cell">
                                        {t('admin.checkin.checked_in')}
                                    </Th>
                                    <Th />
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {registrations.data.map((registration) => (
                                    <tr
                                        key={registration.ulid}
                                        className="hover:bg-accent/50"
                                    >
                                        <td className="p-3">
                                            <span className="block font-medium">
                                                {registration.registrant_name}
                                            </span>
                                            <span className="text-muted-foreground block text-xs">
                                                {registration.registrant_phone ??
                                                    registration.registrant_email ??
                                                    ''}
                                            </span>
                                        </td>

                                        <td className="hidden p-3 tabular-nums sm:table-cell">
                                            {formatNumber(registration.seats)}
                                        </td>

                                        <td className="hidden p-3 md:table-cell">
                                            {registration.amount_due === 0
                                                ? t('public.events.free')
                                                : formatCurrency(
                                                      registration.amount_due,
                                                  )}
                                        </td>

                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    STATUS_VARIANT[
                                                        registration.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {registration.status_label}
                                            </Badge>
                                        </td>

                                        <td className="text-muted-foreground hidden p-3 lg:table-cell">
                                            {registration.checkin ? (
                                                <span className="inline-flex items-center gap-1">
                                                    <CheckCircle2
                                                        className="text-brand-green-600 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {formatDateTime(
                                                        registration.checkin
                                                            .checked_in_at,
                                                    )}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>

                                        <td className="p-3 text-end">
                                            {registration.status ===
                                                'waitlisted' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        router.post(
                                                            `/admin/events/${event.ulid}/registrations/${registration.ulid}/promote`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <ArrowUpCircle
                                                        className="me-1 size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                    {t('admin.events.promote')}
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination meta={registrations.meta} />
            </div>
        </AdminLayout>
    );
}

/**
 * A walk-in: someone who turned up having never registered.
 *
 * They get a registration like anybody else — including a pass — so the gate
 * count stays honest and attendance is one number rather than two.
 */
function WalkInForm({ event }: { event: AdminEvent }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: '',
        phone: '',
        email: '',
        guests_count: '0',
        notes: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post(`/admin/events/${event.ulid}/registrations`, {
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
                {t('admin.events.walk_in')}
            </Button>
        );
    }

    return (
        <Card>
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-4">
                    <p className="text-muted-foreground text-sm">
                        {t('admin.events.walk_in_help')}
                    </p>

                    <div className="grid gap-3 sm:grid-cols-4">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="walk_in_name">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="walk_in_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="walk_in_phone">
                                {t('common.labels.phone')}
                            </Label>
                            <Input
                                id="walk_in_phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                            />
                            <InputError message={form.errors.phone} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="walk_in_guests">
                                {t('member.events.guests')}
                            </Label>
                            <Input
                                id="walk_in_guests"
                                type="number"
                                min="0"
                                max="10"
                                value={form.data.guests_count}
                                onChange={(e) =>
                                    form.setData('guests_count', e.target.value)
                                }
                            />
                            <InputError message={form.errors.guests_count} />
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

function Th({
    children,
    className,
}: {
    children?: React.ReactNode;
    className?: string;
}) {
    return (
        <th
            scope="col"
            className={`p-3 text-start text-xs font-medium tracking-wide uppercase ${className ?? ''}`}
        >
            {children}
        </th>
    );
}
