import { Link, router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { AdminMember, Paginated } from '@/types/member';

type Option = { value: string; label: string };

type Props = {
    members: Paginated<AdminMember>;
    filters: Record<string, string | null>;
    options: {
        statuses: Option[];
        relation_types: Option[];
        blood_groups: Option[];
        batches: Option[];
    };
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    approved: 'default',
    pending: 'secondary',
    under_review: 'secondary',
    rejected: 'destructive',
    suspended: 'destructive',
    archived: 'outline',
};

export default function MembersIndex({ members, filters, options }: Props) {
    const { t, locale } = useTranslation();
    const getInitials = useInitials();
    const { term, setTerm } = useDebouncedSearch(
        filters.q ?? '',
        '/admin/members',
    );

    const hasFilters = Object.values(filters).some(Boolean);

    const setFilter = (key: string, value: string) => {
        router.get(
            '/admin/members',
            { ...filters, [key]: value === '__all' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title={t('admin.nav.members')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.nav.members')}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {formatNumber(members.meta.total, locale)}
                    </p>
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
                            placeholder={t('admin.members.search_placeholder')}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <FilterSelect
                        value={filters.status}
                        options={options.statuses}
                        placeholder={t('common.labels.status')}
                        onChange={(value) => setFilter('status', value)}
                    />
                    <FilterSelect
                        value={filters.batch_id}
                        options={options.batches}
                        placeholder={t('common.labels.batch')}
                        onChange={(value) => setFilter('batch_id', value)}
                    />

                    {hasFilters && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.get('/admin/members')}
                        >
                            <X className="me-1 size-3.5" aria-hidden="true" />
                            {t('common.actions.reset')}
                        </Button>
                    )}
                </div>

                {members.data.length === 0 ? (
                    <EmptyState
                        title={t('common.states.no_results')}
                        description={t('admin.members.empty')}
                    />
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-start">
                                <tr>
                                    <Th>{t('common.labels.name')}</Th>
                                    <Th className="hidden md:table-cell">
                                        {t('common.labels.batch')}
                                    </Th>
                                    <Th className="hidden lg:table-cell">
                                        {t('admin.verification.assign_number')}
                                    </Th>
                                    <Th>{t('common.labels.status')}</Th>
                                    <Th className="hidden lg:table-cell">
                                        {t('common.labels.date')}
                                    </Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {members.data.map((member) => (
                                    <tr
                                        key={member.ulid}
                                        className="hover:bg-accent/50"
                                    >
                                        <td className="p-3">
                                            <Link
                                                href={`/admin/members/${member.ulid}`}
                                                className="flex items-center gap-3"
                                            >
                                                <Avatar className="size-8 shrink-0">
                                                    {member.photo_url && (
                                                        <AvatarImage
                                                            src={
                                                                member.photo_url
                                                            }
                                                            alt=""
                                                        />
                                                    )}
                                                    <AvatarFallback>
                                                        {getInitials(
                                                            member.full_name,
                                                        )}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span className="min-w-0">
                                                    <span className="block truncate font-medium">
                                                        {member.full_name}
                                                    </span>
                                                    <span className="text-muted-foreground block truncate text-xs">
                                                        {member.relation_label}
                                                    </span>
                                                </span>
                                            </Link>
                                        </td>
                                        <td className="hidden p-3 md:table-cell">
                                            {member.batch ?? '—'}
                                        </td>
                                        <td className="tabular-id hidden p-3 lg:table-cell">
                                            {member.membership_no ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    STATUS_VARIANT[
                                                        member.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {member.status_label}
                                            </Badge>
                                        </td>
                                        <td className="text-muted-foreground hidden p-3 lg:table-cell">
                                            {formatDate(
                                                member.registered_at,
                                                locale,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination meta={members.meta} links={members.links} />
            </div>
        </AdminLayout>
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
            className={`p-3 text-start font-medium ${className ?? ''}`}
        >
            {children}
        </th>
    );
}

function FilterSelect({
    value,
    options,
    placeholder,
    onChange,
}: {
    value: string | null;
    options: Option[];
    placeholder: string;
    onChange: (value: string) => void;
}) {
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
