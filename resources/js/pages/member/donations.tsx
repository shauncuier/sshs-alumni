import { Gift } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatDate } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { Donation } from '@/types/money';
import type { Paginated } from '@/types/member';

type Props = {
    donations: Paginated<Donation>;
    total: number;
    currency: string;
};

export default function MemberDonations({ donations, total }: Props) {
    const { t } = useTranslation();

    return (
        <MemberLayout title={t('member.money.donations_title')}>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('member.money.donations_title')}
                    </h1>

                    <div className="text-end">
                        <p className="text-muted-foreground text-xs">
                            {t('member.money.total_given')}
                        </p>
                        <p className="text-xl font-semibold tabular-nums">
                            {formatCurrency(total)}
                        </p>
                    </div>
                </div>

                {donations.data.length === 0 ? (
                    <EmptyState
                        icon={Gift}
                        title={t('common.states.empty')}
                        description={t('member.money.donations_empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {donations.data.map((donation) => (
                            <div
                                key={donation.ulid}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {donation.campaign ??
                                            t('admin.donations.description')}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {formatDate(donation.received_at)}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    {donation.is_anonymous && (
                                        <Badge variant="outline">
                                            {t('admin.donations.anonymous')}
                                        </Badge>
                                    )}
                                    <span className="font-medium tabular-nums">
                                        {formatCurrency(donation.amount)}
                                    </span>
                                    <Badge
                                        variant={
                                            donation.status === 'received'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {donation.status_label}
                                    </Badge>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination meta={donations.meta} />
            </div>
        </MemberLayout>
    );
}
