import { Deferred, Link, router, useForm } from '@inertiajs/react';
import { Contact as ContactIcon, Plus, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Skeleton } from '@/components/ui/skeleton';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Contact, CrmOptions } from '@/types/crm';
import type { Paginated } from '@/types/member';

type Props = {
    contacts: Paginated<Contact>;
    filters: Record<string, string | null>;
    options: CrmOptions;
    counts?: Record<string, number>;
    can: { create: boolean };
};

export default function ContactsIndex({
    contacts,
    filters,
    options,
    counts,
    can,
}: Props) {
    const { t } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/crm/contacts',
    );

    const hasFilters = Object.values(filters).some(Boolean);

    const setFilter = (key: string, value: string) => {
        router.get(
            '/admin/crm/contacts',
            { ...filters, [key]: value === '__all' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title={t('admin.crm.contacts')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.crm.contacts')}
                    </h1>

                    <div className="flex flex-wrap gap-2">
                        <Button asChild size="sm" variant="outline">
                            <Link href="/admin/crm/pipeline">
                                {t('admin.crm.pipeline')}
                            </Link>
                        </Button>

                        {can.create && (
                            <CreateContactDialog options={options} />
                        )}
                    </div>
                </div>

                <Deferred data="counts" fallback={<StageStripSkeleton />}>
                    <StageStrip
                        counts={counts ?? {}}
                        options={options}
                        active={filters.stage}
                        onPick={(stage) => setFilter('stage', stage)}
                    />
                </Deferred>

                <div className="flex flex-wrap gap-2">
                    <div className="relative min-w-60 flex-1">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder={t('admin.crm.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <FilterSelect
                        value={filters.type}
                        options={options.types}
                        placeholder={t('common.labels.status')}
                        onChange={(value) => setFilter('type', value)}
                    />

                    <FilterSelect
                        value={filters.owner}
                        options={[
                            { value: 'me', label: t('admin.crm.owner_mine') },
                            {
                                value: 'none',
                                label: t('admin.crm.owner_unassigned'),
                            },
                        ]}
                        placeholder={t('admin.crm.owner')}
                        onChange={(value) => setFilter('owner', value)}
                    />

                    <FilterSelect
                        value={filters.tag}
                        options={options.tags.map((tag) => ({
                            value: String(tag.id),
                            label: tag.name,
                        }))}
                        placeholder={t('admin.crm.tags')}
                        onChange={(value) => setFilter('tag', value)}
                    />

                    {hasFilters && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/crm/contacts')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {contacts.data.length === 0 ? (
                    <EmptyState
                        icon={ContactIcon}
                        title={t('common.states.no_results')}
                        description={t('admin.crm.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {contacts.data.map((contact) => (
                            <Link
                                key={contact.ulid}
                                href={`/admin/crm/contacts/${contact.ulid}`}
                                className="hover:bg-accent/50 flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="flex flex-wrap items-center gap-2 font-medium">
                                        <span className="truncate">
                                            {contact.name}
                                        </span>

                                        {contact.member && (
                                            <Badge
                                                variant="secondary"
                                                className="text-xs"
                                            >
                                                {t('admin.crm.member')}
                                            </Badge>
                                        )}

                                        {(contact.tags ?? []).map((tag) => (
                                            <span
                                                key={tag.id}
                                                className="rounded-full border px-2 py-0.5 text-xs"
                                                style={
                                                    tag.color
                                                        ? {
                                                              borderColor:
                                                                  tag.color,
                                                              color: tag.color,
                                                          }
                                                        : undefined
                                                }
                                            >
                                                {tag.name}
                                            </span>
                                        ))}
                                    </p>

                                    <p className="text-muted-foreground truncate text-sm">
                                        {contact.organization_name ??
                                            contact.email ??
                                            contact.phone ??
                                            contact.type_label}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    {(contact.open_tasks_count ?? 0) > 0 && (
                                        <Badge variant="outline">
                                            {formatNumber(
                                                contact.open_tasks_count ?? 0,
                                            )}{' '}
                                            {t('admin.crm.tasks')}
                                        </Badge>
                                    )}

                                    <span className="text-muted-foreground hidden sm:inline">
                                        {contact.owner?.name ??
                                            t('admin.crm.owner_none')}
                                    </span>

                                    <span className="text-muted-foreground hidden lg:inline">
                                        {formatDate(contact.last_activity_at)}
                                    </span>

                                    <Badge>{contact.pipeline_label}</Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination meta={contacts.meta} />
            </div>
        </AdminLayout>
    );
}

/**
 * The pipeline at a glance, and a one-click filter into any stage.
 */
function StageStrip({
    counts,
    options,
    active,
    onPick,
}: {
    counts: Record<string, number>;
    options: CrmOptions;
    active: string | null;
    onPick: (stage: string) => void;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.stages.map((stage) => (
                <button
                    key={stage.value}
                    type="button"
                    onClick={() =>
                        onPick(active === stage.value ? '__all' : stage.value)
                    }
                    className={
                        active === stage.value
                            ? 'border-brand-green-800 bg-brand-green-100 text-brand-green-900 rounded-md border px-3 py-1.5 text-xs font-medium'
                            : 'hover:bg-accent rounded-md border px-3 py-1.5 text-xs'
                    }
                >
                    {stage.label}
                    <span className="ms-1.5 tabular-nums opacity-70">
                        {formatNumber(counts[stage.value] ?? 0)}
                    </span>
                </button>
            ))}
        </div>
    );
}

function StageStripSkeleton() {
    return (
        <div className="flex flex-wrap gap-2">
            {Array.from({ length: 8 }).map((_, index) => (
                <Skeleton key={index} className="h-7 w-24" />
            ))}
        </div>
    );
}

function FilterSelect({
    value,
    options,
    placeholder,
    onChange,
}: {
    value: string | null;
    options: Array<{ value: string; label: string }>;
    placeholder: string;
    onChange: (value: string) => void;
}) {
    if (options.length === 0) {
        return null;
    }

    return (
        <Select value={value ?? '__all'} onValueChange={onChange}>
            <SelectTrigger className="w-auto min-w-40">
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="__all">{placeholder}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function CreateContactDialog({ options }: { options: CrmOptions }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        type: options.types[0]?.value ?? 'prospect',
        name: '',
        organization_name: '',
        email: '',
        phone: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/admin/crm/contacts', {
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
                    {t('admin.crm.create')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.crm.create')}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="name">{t('common.labels.name')}</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
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

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="email">
                                {t('common.labels.email')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="phone">
                                {t('common.labels.phone')}
                            </Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                            />
                            <InputError message={form.errors.phone} />
                        </div>
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
