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
                <div className="rounded-2xl border border-dashed border-teal-200/80 p-10 text-center">
                    <EmptyState
                        title={t('jubilee.sponsors.title')}
                        description={t('jubilee.sponsors.empty')}
                    />
                </div>
            ) : (
                <div className="space-y-12">
                    {sponsors.map((group) => (
                        <section key={group.tier}>
                            <div className="flex items-center gap-3">
                                <span className="inline-block h-4 w-1 rounded-full bg-amber-500" />
                                <h2 className="text-sm font-bold tracking-widest text-amber-700 uppercase">
                                    {group.label}
                                </h2>
                            </div>

                            <ul className="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
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
            className="h-14 w-auto object-contain transition-transform duration-300 group-hover:scale-105"
        />
    ) : (
        <span className="text-center text-sm font-bold text-slate-800">
            {sponsor.name}
        </span>
    );

    const baseClasses =
        'glass-card-hover group flex h-28 w-full flex-col items-center justify-center rounded-2xl border border-teal-500/15 bg-white/80 p-5 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-amber-400/50 hover:shadow-md';

    if (!sponsor.website) {
        return <div className={baseClasses}>{inner}</div>;
    }

    return (
        <a
            href={sponsor.website}
            target="_blank"
            rel="noopener noreferrer"
            className={baseClasses}
        >
            {inner}
        </a>
    );
}
