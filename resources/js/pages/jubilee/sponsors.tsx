import { JubileeLayout } from '@/components/public/jubilee-layout';
import { EmptyState } from '@/components/shared/empty-state';
import { useTranslation } from '@/hooks/use-translation';
import type { PublicEvent } from '@/types/event';

type Sponsor = {
    name: string;
    website: string | null;
    logo_url: string | null;
    tier: string | null;
    tier_label: string | null;
};

type TierGroup = {
    tier: string;
    label: string;
    sponsors: Sponsor[];
};

type Props = {
    event: PublicEvent | null;
    sponsors: TierGroup[];
};

/**
 * The sponsor wall, by tier.
 *
 * A sponsor who asked not to be listed is absent from the payload entirely,
 * not hidden with CSS.
 */
export default function Sponsors({ event, sponsors }: Props) {
    const { t } = useTranslation();

    return (
        <JubileeLayout
            event={event}
            active="sponsors"
            title={t('jubilee.sponsors.title')}
        >
            {sponsors.length === 0 ? (
                <EmptyState
                    title={t('jubilee.sponsors.title')}
                    description={t('jubilee.sponsors.empty')}
                />
            ) : (
                <div className="space-y-10">
                    {sponsors.map((group) => (
                        <section key={group.tier}>
                            <h2 className="text-brand-gold-600 text-sm font-semibold tracking-wide uppercase">
                                {group.label}
                            </h2>

                            <ul className="mt-4 flex flex-wrap items-center gap-6">
                                {group.sponsors.map((sponsor) => (
                                    <li key={sponsor.name}>
                                        <SponsorMark sponsor={sponsor} />
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
            )}
        </JubileeLayout>
    );
}

function SponsorMark({ sponsor }: { sponsor: Sponsor }) {
    const inner = sponsor.logo_url ? (
        <img
            src={sponsor.logo_url}
            alt={sponsor.name}
            className="h-14 w-auto object-contain"
        />
    ) : (
        <span className="font-medium">{sponsor.name}</span>
    );

    if (!sponsor.website) {
        return (
            <span className="bg-card block rounded-lg border p-4">{inner}</span>
        );
    }

    return (
        <a
            href={sponsor.website}
            target="_blank"
            rel="noopener noreferrer"
            className="bg-card block rounded-lg border p-4 transition-opacity hover:opacity-80"
        >
            {inner}
        </a>
    );
}
