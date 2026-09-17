import { Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, Sparkles, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EventDate } from '@/components/public/event-date';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminEvent } from '@/types/event';
import type { Option } from '@/types/batch';
import type { Paginated } from '@/types/member';

type Props = {
    events: Paginated<AdminEvent>;
    filters: { q: string | null; status: string | null };
    options: { statuses: Option[]; types: Option[] };
    can: { create: boolean };
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    published: 'default',
    registration_open: 'default',
    registration_closed: 'secondary',
    draft: 'outline',
    completed: 'secondary',
    cancelled: 'destructive',
};

export default function EventsIndex({ events, filters, options, can }: Props) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/events',
    );

    const hasFilters = Boolean(filters.q || filters.status);

    return (
        <AdminLayout title={t('admin.nav.events')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.nav.events')}
                    </h1>

                    {can.create && <CreateEventDialog options={options} />}
                </div>

                <div className="flex flex-wrap gap-2">
                    <div className="relative min-w-60 flex-1">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder={t('admin.events.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <Select
                        value={filters.status ?? '__all'}
                        onValueChange={(value) =>
                            router.get(
                                '/admin/events',
                                {
                                    ...filters,
                                    status:
                                        value === '__all' ? undefined : value,
                                },
                                { preserveState: true, replace: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-auto min-w-44">
                            <SelectValue
                                placeholder={t('common.labels.status')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">
                                {t('common.labels.status')}
                            </SelectItem>
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

                    {hasFilters && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/events')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {events.data.length === 0 ? (
                    <EmptyState
                        title={t('common.states.no_results')}
                        description={t('admin.events.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {events.data.map((event) => (
                            <Link
                                key={event.ulid}
                                href={`/admin/events/${event.ulid}`}
                                className="hover:bg-accent/50 flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="flex items-center gap-2 font-medium">
                                        {event.is_flagship && (
                                            <Sparkles
                                                className="text-brand-gold-600 size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                        )}
                                        <span className="truncate">
                                            {event.title}
                                        </span>
                                    </p>
                                    <EventDate
                                        event={event}
                                        className="text-muted-foreground mt-1 text-sm"
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <span className="text-muted-foreground text-sm">
                                        {formatNumber(
                                            event.registrations_count ?? 0,
                                        )}
                                    </span>
                                    <Badge
                                        variant={
                                            STATUS_VARIANT[event.status] ??
                                            'secondary'
                                        }
                                    >
                                        {event.status_label}
                                    </Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination meta={events.meta} />
            </div>
        </AdminLayout>
    );
}

/**
 * A new event is always a draft with an unannounced date, whatever this form
 * posts. Publishing and announcing are separate, permissioned acts.
 */
function CreateEventDialog({
    options,
}: {
    options: { statuses: Option[]; types: Option[] };
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        type: options.types[0]?.value ?? 'general',
        title: '',
        summary: '',
        registration_required: false,
        currency: 'BDT',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/admin/events', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus className="me-1 size-4" aria-hidden="true" />
                    {t('admin.events.create')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.events.create')}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="title">{t('common.labels.name')}</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="type">
                            {t('common.labels.status')}
                        </Label>
                        <Select
                            value={form.data.type}
                            onValueChange={(value) =>
                                form.setData('type', value)
                            }
                        >
                            <SelectTrigger id="type">
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
                        <InputError message={form.errors.type} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="summary">
                            {t('common.labels.description')}
                        </Label>
                        <Input
                            id="summary"
                            value={form.data.summary}
                            onChange={(e) =>
                                form.setData('summary', e.target.value)
                            }
                        />
                        <InputError message={form.errors.summary} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {t('common.actions.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
