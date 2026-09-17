import { Link, router } from '@inertiajs/react';
import { LayoutGrid, List, Search, X } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { formatNumber } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { DirectoryMember, Paginated } from '@/types/member';

type Option = { value: string; label: string };

/** Free-text columns come back as bare strings; the select wants pairs. */
const toOptions = (values: string[]): Option[] =>
    values.map((value) => ({ value, label: value }));

type Props = {
    members: Paginated<DirectoryMember>;
    filters: Record<string, string | null>;
    options: {
        batches: Option[];
        relation_types: Option[];
        blood_groups: Option[];
        districts: string[];
        industries: string[];
        countries: string[];
        occupations: string[];
    };
};

export default function Directory({ members, filters, options }: Props) {
    const { t, choice } = useTranslation();
    const [view, setView] = useState<'grid' | 'list'>('grid');
    const { term, setTerm } = useDebouncedSearch(filters.q ?? '', '/directory');

    const activeCount = Object.values(filters).filter(Boolean).length;

    const setFilter = (key: string, value: string) => {
        router.get(
            '/directory',
            { ...filters, [key]: value === '__all' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <MemberLayout title={t('member.nav.directory')}>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {t('member.nav.directory')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {choice(
                                'member.directory.count',
                                members.meta.total,
                                { count: formatNumber(members.meta.total) },
                            )}
                        </p>
                    </div>

                    <div className="flex items-center gap-1 rounded-md border p-1">
                        <Button
                            variant={view === 'grid' ? 'secondary' : 'ghost'}
                            size="icon"
                            onClick={() => setView('grid')}
                            aria-label={t('member.directory.grid_view')}
                            aria-pressed={view === 'grid'}
                        >
                            <LayoutGrid className="size-4" aria-hidden="true" />
                        </Button>
                        <Button
                            variant={view === 'list' ? 'secondary' : 'ghost'}
                            size="icon"
                            onClick={() => setView('list')}
                            aria-label={t('member.directory.list_view')}
                            aria-pressed={view === 'list'}
                        >
                            <List className="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </div>

                <div className="space-y-3">
                    <div className="relative">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder={t(
                                'member.directory.search_placeholder',
                            )}
                            className="ps-9"
                            aria-label={t('common.actions.search')}
                        />
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <FilterSelect
                            value={filters.batch_id}
                            options={options.batches}
                            placeholder={t('common.labels.batch')}
                            onChange={(value) => setFilter('batch_id', value)}
                        />
                        <FilterSelect
                            value={filters.district}
                            options={toOptions(options.districts)}
                            placeholder={t('public.join.fields.district')}
                            onChange={(value) => setFilter('district', value)}
                        />
                        <FilterSelect
                            value={filters.industry}
                            options={toOptions(options.industries)}
                            placeholder={t('public.join.fields.industry')}
                            onChange={(value) => setFilter('industry', value)}
                        />
                        <FilterSelect
                            value={filters.country}
                            options={toOptions(options.countries)}
                            placeholder={t('public.join.fields.country')}
                            onChange={(value) => setFilter('country', value)}
                        />
                        <FilterSelect
                            value={filters.occupation}
                            options={toOptions(options.occupations)}
                            placeholder={t('public.join.fields.occupation')}
                            onChange={(value) => setFilter('occupation', value)}
                        />
                        <FilterSelect
                            value={filters.relation_type}
                            options={options.relation_types}
                            placeholder={t('public.join.fields.relation_type')}
                            onChange={(value) =>
                                setFilter('relation_type', value)
                            }
                        />
                        <FilterSelect
                            value={filters.blood_group}
                            options={options.blood_groups}
                            placeholder={t('public.join.fields.blood_group')}
                            onChange={(value) =>
                                setFilter('blood_group', value)
                            }
                        />

                        {activeCount > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => router.get('/directory')}
                            >
                                <X
                                    className="me-1 size-3.5"
                                    aria-hidden="true"
                                />
                                {t('common.actions.reset')}
                            </Button>
                        )}
                    </div>
                </div>

                {members.data.length === 0 ? (
                    <EmptyState
                        title={t('common.states.no_results')}
                        description={t('member.directory.empty')}
                    />
                ) : view === 'grid' ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {members.data.map((member) => (
                            <MemberCard key={member.ulid} member={member} />
                        ))}
                    </div>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {members.data.map((member) => (
                            <MemberRow key={member.ulid} member={member} />
                        ))}
                    </div>
                )}

                <Pagination meta={members.meta} />
            </div>
        </MemberLayout>
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
    // A filter with nothing to offer is dropped rather than rendered empty.
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

/**
 * A directory card shows only what the payload carries. Fields the member
 * hid are absent from the data, so there is nothing here to guard against —
 * the filtering already happened on the server.
 */
function MemberCard({ member }: { member: DirectoryMember }) {
    const getInitials = useInitials();

    return (
        <Card className="transition-shadow hover:shadow-md">
            <CardContent className="flex gap-4 p-4">
                <Avatar className="size-14 shrink-0">
                    {member.photo_url && (
                        <AvatarImage src={member.photo_url} alt="" />
                    )}
                    <AvatarFallback>
                        {getInitials(member.full_name)}
                    </AvatarFallback>
                </Avatar>

                <div className="min-w-0 flex-1">
                    <Link
                        href={`/directory/${member.ulid}`}
                        className="hover:text-brand-green-800 font-semibold"
                    >
                        {member.full_name}
                    </Link>

                    {member.batch && (
                        <Badge
                            variant="secondary"
                            className="ms-2 align-middle"
                        >
                            {member.batch}
                        </Badge>
                    )}

                    {member.occupation && (
                        <p className="text-muted-foreground mt-1 truncate text-sm">
                            {member.occupation}
                            {member.organization
                                ? ` · ${member.organization}`
                                : ''}
                        </p>
                    )}

                    {(member.city || member.district) && (
                        <p className="text-muted-foreground mt-0.5 truncate text-xs">
                            {[member.city, member.district]
                                .filter(Boolean)
                                .join(', ')}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function MemberRow({ member }: { member: DirectoryMember }) {
    const getInitials = useInitials();

    return (
        <Link
            href={`/directory/${member.ulid}`}
            className="hover:bg-accent flex items-center gap-3 p-3 transition-colors"
        >
            <Avatar className="size-9 shrink-0">
                {member.photo_url && (
                    <AvatarImage src={member.photo_url} alt="" />
                )}
                <AvatarFallback>{getInitials(member.full_name)}</AvatarFallback>
            </Avatar>

            <span className="min-w-0 flex-1 truncate font-medium">
                {member.full_name}
            </span>

            {member.occupation && (
                <span className="text-muted-foreground hidden min-w-0 flex-1 truncate text-sm sm:block">
                    {member.occupation}
                </span>
            )}

            {member.batch && (
                <Badge variant="secondary" className="shrink-0">
                    {member.batch}
                </Badge>
            )}
        </Link>
    );
}
