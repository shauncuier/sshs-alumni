import { router, useForm } from '@inertiajs/react';
import { Plus, Ticket, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatNumber } from '@/lib/format';
import type { AdminEvent } from '@/types/event';

type Props = {
    event: AdminEvent;
    editable: boolean;
};

/**
 * Ticket types for one event.
 *
 * `sold_count` is shown but never editable — it belongs to the registrar. A
 * type that has been sold is withdrawn rather than deleted, so the pricing of
 * existing registrations is not orphaned.
 */
export function TicketTypes({ event, editable }: Props) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const tickets = event.ticket_types ?? [];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between gap-2 text-base">
                    <span className="flex items-center gap-2">
                        <Ticket className="size-4" aria-hidden="true" />
                        {t('admin.events.tickets')}
                    </span>

                    {editable && !adding && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setAdding(true)}
                        >
                            <Plus
                                className="me-1 size-3.5"
                                aria-hidden="true"
                            />
                            {t('admin.events.ticket_add')}
                        </Button>
                    )}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {tickets.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {t('admin.events.tickets_empty')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {tickets.map((ticket) => (
                            <li
                                key={ticket.id}
                                className="flex flex-wrap items-center justify-between gap-3 p-3"
                            >
                                <div className="min-w-0">
                                    <p className="flex items-center gap-2 font-medium">
                                        {ticket.name}
                                        {!ticket.is_active && (
                                            <Badge variant="outline">
                                                {t('common.states.no')}
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {ticket.price === 0
                                            ? t('public.events.free')
                                            : formatCurrency(ticket.price)}
                                        {' · '}
                                        {ticket.quantity === null
                                            ? t('admin.events.ticket_unlimited')
                                            : formatNumber(ticket.quantity)}
                                        {' · '}
                                        {t('admin.events.ticket_sold', {
                                            sold: formatNumber(
                                                ticket.sold_count,
                                            ),
                                        })}
                                    </p>
                                </div>

                                {editable && (
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={t('common.actions.delete')}
                                        onClick={() =>
                                            router.delete(
                                                `/admin/events/${event.ulid}/tickets/${ticket.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
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

                {editable && adding && (
                    <TicketForm event={event} onDone={() => setAdding(false)} />
                )}
            </CardContent>
        </Card>
    );
}

function TicketForm({
    event,
    onDone,
}: {
    event: AdminEvent;
    onDone: () => void;
}) {
    const { t } = useTranslation();

    const form = useForm({
        name: '',
        description: '',
        price: '0',
        quantity: '',
        per_person_limit: '1',
        is_active: true,
        display_order: '0',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post(`/admin/events/${event.ulid}/tickets`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onDone();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3 rounded-md border p-4">
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <Label htmlFor="ticket_name">
                        {t('common.labels.name')}
                    </Label>
                    <Input
                        id="ticket_name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="ticket_price">
                        {t('admin.events.ticket_price')}
                    </Label>
                    <Input
                        id="ticket_price"
                        type="number"
                        step="0.01"
                        min="0"
                        value={form.data.price}
                        onChange={(e) => form.setData('price', e.target.value)}
                        required
                    />
                    <InputError message={form.errors.price} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="ticket_quantity">
                        {t('admin.events.ticket_quantity')}
                    </Label>
                    <Input
                        id="ticket_quantity"
                        type="number"
                        min="1"
                        placeholder={t('admin.events.ticket_unlimited')}
                        value={form.data.quantity}
                        onChange={(e) =>
                            form.setData('quantity', e.target.value)
                        }
                    />
                    <InputError message={form.errors.quantity} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="per_person_limit">
                        {t('member.events.guests')}
                    </Label>
                    <Input
                        id="per_person_limit"
                        type="number"
                        min="1"
                        value={form.data.per_person_limit}
                        onChange={(e) =>
                            form.setData('per_person_limit', e.target.value)
                        }
                    />
                    <InputError message={form.errors.per_person_limit} />
                </div>
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="ticket_active"
                    checked={form.data.is_active}
                    onCheckedChange={(checked) =>
                        form.setData('is_active', checked === true)
                    }
                />
                <Label htmlFor="ticket_active">
                    {t('admin.events.ticket_active')}
                </Label>
            </div>

            <div className="flex gap-2">
                <Button type="submit" size="sm" disabled={form.processing}>
                    {t('common.actions.save')}
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    onClick={onDone}
                >
                    {t('common.actions.cancel')}
                </Button>
            </div>
        </form>
    );
}
