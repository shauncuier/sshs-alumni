import { Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, X } from 'lucide-react';
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
    DialogDescription,
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
import type { Paginated } from '@/types/member';
import type { AdminBatchRow, Option } from '@/types/batch';

type Props = {
    batches: Paginated<AdminBatchRow>;
    filters: { q: string | null };
    options: { statuses: Option[] };
    can: { create: boolean };
};

export default function BatchesIndex({
    batches,
    filters,
    options,
    can,
}: Props) {
    const { t, locale } = useTranslation();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/batches',
    );

    return (
        <AdminLayout title={t('admin.nav.batches')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t('admin.nav.batches')}
                        </h1>
                        <p
                            lang={locale}
                            className="text-muted-foreground mt-1 max-w-2xl text-sm"
                        >
                            {t('admin.batches.intro')}
                        </p>
                    </div>

                    {can.create && <CreateBatchDialog options={options} />}
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
                            placeholder={t('admin.batches.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    {filters.q !== null && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/batches')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {batches.data.length === 0 ? (
                    <EmptyState
                        title={t('common.states.no_results')}
                        description={t('admin.batches.empty')}
                    />
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-start">
                                <tr>
                                    <Th>{t('common.labels.batch')}</Th>
                                    <Th className="hidden sm:table-cell">
                                        {t('admin.batches.ssc_year')}
                                    </Th>
                                    <Th>{t('admin.batches.members_count')}</Th>
                                    <Th className="hidden md:table-cell">
                                        {t('admin.batches.coordinators')}
                                    </Th>
                                    <Th>{t('common.labels.status')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {batches.data.map((batch) => (
                                    <tr
                                        key={batch.id}
                                        className="hover:bg-accent/50"
                                    >
                                        <td className="p-3">
                                            <Link
                                                href={`/admin/batches/${batch.id}`}
                                                lang={locale}
                                                className="font-medium hover:underline"
                                            >
                                                {batch.display_name}
                                            </Link>
                                        </td>
                                        {/* A year is a number, not an
                                            identifier, so it follows the
                                            reading language. */}
                                        <td className="hidden p-3 sm:table-cell">
                                            {formatNumber(
                                                batch.ssc_year,
                                                locale,
                                            )}
                                        </td>
                                        <td className="p-3">
                                            {formatNumber(
                                                batch.members_count,
                                                locale,
                                            )}
                                        </td>
                                        <td className="text-muted-foreground hidden p-3 md:table-cell">
                                            {batch.coordinators.length === 0
                                                ? t(
                                                      'admin.batches.no_coordinators',
                                                  )
                                                : batch.coordinators
                                                      .map((one) => one.name)
                                                      .join(', ')}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    batch.status === 'active'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {batch.status_label}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination meta={batches.meta} />
            </div>
        </AdminLayout>
    );
}

/**
 * The slug is derived from the SSC year server-side, so it is deliberately
 * absent from this form — two batches for one year is a data error, not a
 * naming choice.
 */
function CreateBatchDialog({ options }: { options: { statuses: Option[] } }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: '',
        name_bn: '',
        ssc_year: '',
        description: '',
        description_bn: '',
        status: 'active',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/admin/batches', {
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
                    {t('admin.batches.create')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{t('admin.batches.create')}</DialogTitle>
                        <DialogDescription>
                            {t('admin.batches.intro')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="ssc_year">
                                {t('admin.batches.ssc_year')}
                            </Label>
                            <Input
                                id="ssc_year"
                                type="number"
                                inputMode="numeric"
                                value={form.data.ssc_year}
                                onChange={(event) =>
                                    form.setData('ssc_year', event.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.ssc_year} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="status">
                                {t('common.labels.status')}
                            </Label>
                            <Select
                                value={form.data.status}
                                onValueChange={(value) =>
                                    form.setData('status', value)
                                }
                            >
                                <SelectTrigger id="status">
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
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="name">
                                {t('common.labels.name')} (EN)
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="name_bn">
                                {t('common.labels.name')} (বাংলা)
                            </Label>
                            <Input
                                id="name_bn"
                                lang="bn"
                                value={form.data.name_bn}
                                onChange={(event) =>
                                    form.setData('name_bn', event.target.value)
                                }
                            />
                            <InputError message={form.errors.name_bn} />
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

function Th({
    children,
    className,
}: {
    children: React.ReactNode;
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
