import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PaginationLink, PaginationMeta } from '@/types/member';

type Props = {
    meta: PaginationMeta;
};

export function Pagination({ meta }: Props) {
    const { t } = useTranslation();

    if (meta.last_page <= 1) {
        return null;
    }

    const links = meta.links;
    const previous = links[0];
    const next = links[links.length - 1];
    const numbered = links.slice(1, -1);

    return (
        <nav
            className="flex flex-wrap items-center justify-between gap-3"
            aria-label={t('common.actions.next')}
        >
            <p className="text-muted-foreground text-sm">
                {t('common.labels.showing', {
                    from: formatNumber(meta.from ?? 0),
                    to: formatNumber(meta.to ?? 0),
                    total: formatNumber(meta.total),
                })}
            </p>

            <div className="flex items-center gap-1">
                <PageLink
                    link={previous}
                    aria-label={t('common.actions.previous')}
                >
                    <ChevronLeft
                        className="size-4 rtl:rotate-180"
                        aria-hidden="true"
                    />
                </PageLink>

                {numbered.map((link, index) => (
                    <PageLink key={`${link.label}-${index}`} link={link}>
                        {/^\d+$/.test(link.label)
                            ? formatNumber(Number(link.label))
                            : link.label}
                    </PageLink>
                ))}

                <PageLink link={next} label={t('common.actions.next')}>
                    <ChevronRight
                        className="size-4 rtl:rotate-180"
                        aria-hidden="true"
                    />
                </PageLink>
            </div>
        </nav>
    );
}

function PageLink({
    link,
    label,
    children,
}: {
    link: PaginationLink;
    /** Accessible name for the icon-only previous/next controls. */
    label?: string;
    children: ReactNode;
}) {
    const className = cn(
        'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-2 text-sm transition-colors',
        link.active ? 'bg-brand-green-800 text-white' : 'hover:bg-accent',
        !link.url && 'text-muted-foreground pointer-events-none opacity-50',
    );

    // A disabled page is a span, not a dead link — nothing to tab to.
    if (!link.url) {
        return (
            <span className={className} aria-disabled="true" aria-label={label}>
                {children}
            </span>
        );
    }

    return (
        <Link
            href={link.url}
            preserveScroll
            preserveState
            className={className}
            aria-label={label}
            aria-current={link.active ? 'page' : undefined}
        >
            {children}
        </Link>
    );
}
