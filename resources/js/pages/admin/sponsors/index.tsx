import { router, useForm } from '@inertiajs/react';
import { FileText, Handshake, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { formatCurrency, formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Option, Sponsor, SponsorshipPackage } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    sponsors: Paginated<Sponsor>;
    packages: SponsorshipPackage[];
    filters: { status: string | null };
    options: {
        statuses: Option[];
        tiers: Option[];
        kinds: Option[];
        events: Option[];
    };
    can: { manage: boolean; record: boolean };
};

export default function SponsorsIndex({
    sponsors,
    packages,
    options,
    can,
}: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout title={t('admin.sponsors.title')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.sponsors.title')}
                    </h1>

                    {can.manage && (
                        <AddSponsorForm options={options} packages={packages} />
                    )}
                </div>

                {packages.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {t('admin.sponsors.packages')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y">
                                {packages.map((pkg) => (
                                    <li
                                        key={pkg.id}
                                        className="flex flex-wrap items-center justify-between gap-3 py-2"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {pkg.name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {pkg.tier_label}
                                                {pkg.amount !== null
                                                    ? ` · ${formatCurrency(pkg.amount)}`
                                                    : ''}
                                            </p>
                                        </div>

                                        <Badge variant="secondary">
                                            {pkg.max_slots === null
                                                ? t(
                                                      'admin.sponsors.slots_unlimited',
                                                  )
                                                : t('admin.sponsors.slots', {
                                                      taken: formatNumber(
                                                          pkg.taken,
                                                      ),
                                                      max: formatNumber(
                                                          pkg.max_slots,
                                                      ),
                                                  })}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                {sponsors.data.length === 0 ? (
                    <EmptyState
                        icon={Handshake}
                        title={t('common.states.empty')}
                        description={t('admin.sponsors.empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {sponsors.data.map((sponsor) => (
                            <SponsorRow
                                key={sponsor.ulid}
                                sponsor={sponsor}
                                can={can}
                            />
                        ))}
                    </div>
                )}

                <Pagination meta={sponsors.meta} />
            </div>
        </AdminLayout>
    );
}

function SponsorRow({
    sponsor,
    can,
}: {
    sponsor: Sponsor;
    can: { manage: boolean; record: boolean };
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div className="min-w-0">
                <p className="truncate font-medium">{sponsor.name}</p>
                <p className="text-muted-foreground truncate text-sm">
                    {sponsor.package ?? t('admin.sponsors.custom')}
                    {sponsor.tier_label ? ` · ${sponsor.tier_label}` : ''}
                    {sponsor.event ? ` · ${sponsor.event}` : ''}
                </p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                {sponsor.amount !== null && (
                    <span className="font-medium tabular-nums">
                        {formatCurrency(sponsor.amount)}
                    </span>
                )}

                <Badge
                    variant={
                        sponsor.status === 'paid'
                            ? 'default'
                            : sponsor.status === 'cancelled'
                              ? 'outline'
                              : 'secondary'
                    }
                >
                    {sponsor.status_label}
                </Badge>

                {can.manage && (
                    <label className="flex items-center gap-1.5 text-xs">
                        <Checkbox
                            checked={sponsor.is_public}
                            onCheckedChange={(checked) =>
                                router.put(
                                    `/admin/sponsors/${sponsor.ulid}`,
                                    { is_public: checked === true },
                                    { preserveScroll: true },
                                )
                            }
                        />
                        {t('admin.sponsors.on_wall')}
                    </label>
                )}

                {can.manage && sponsor.status !== 'paid' && (
                    <>
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() =>
                                router.post(
                                    `/admin/sponsors/${sponsor.ulid}/invoice`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <FileText
                                className="me-1 size-3.5"
                                aria-hidden="true"
                            />
                            {t('admin.sponsors.invoice')}
                        </Button>

                        {can.record && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    router.post(
                                        `/admin/sponsors/${sponsor.ulid}/payment`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {t('admin.sponsors.record')}
                            </Button>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}

function AddSponsorForm({
    options,
    packages,
}: {
    options: { kinds: Option[]; events: Option[] };
    packages: SponsorshipPackage[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        name: '',
        kind: options.kinds[0]?.value ?? 'company',
        sponsorship_package_id: '',
        amount: '',
        contact_name: '',
        contact_email: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/sponsors', {
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.sponsors.add')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="sponsor_name">
                                {t('common.labels.name')}
                            </Label>
                            <Input
                                id="sponsor_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="kind">
                                {t('admin.sponsors.kind')}
                            </Label>
                            <Select
                                value={form.data.kind}
                                onValueChange={(value) =>
                                    form.setData('kind', value)
                                }
                            >
                                <SelectTrigger id="kind">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.kinds.map((kind) => (
                                        <SelectItem
                                            key={kind.value}
                                            value={kind.value}
                                        >
                                            {kind.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="sponsorship_package_id">
                                {t('admin.sponsors.package')}
                            </Label>
                            <Select
                                value={
                                    form.data.sponsorship_package_id || '__none'
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'sponsorship_package_id',
                                        value === '__none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="sponsorship_package_id">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none">
                                        {t('admin.sponsors.custom')}
                                    </SelectItem>
                                    {packages.map((pkg) => (
                                        <SelectItem
                                            key={pkg.id}
                                            value={String(pkg.id)}
                                        >
                                            {pkg.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <p className="text-muted-foreground text-xs">
                        {t('admin.sponsors.wall_hint')}
                    </p>

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
