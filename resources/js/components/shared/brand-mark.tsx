import { useState } from 'react';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type Props = {
    /** `association` is the platform's own identity; `school` appears on school pages. */
    mark?: 'association' | 'school';
    size?: 'sm' | 'md' | 'lg';
    /** Show the organisation name beside the mark. */
    withName?: boolean;
    className?: string;
};

const SIZES = {
    sm: 'size-8',
    md: 'size-10',
    lg: 'size-16',
} as const;

/**
 * The official logo, with a graceful fallback.
 *
 * The association supplied real artwork; until the files are in place — or if
 * one ever fails to load — this renders a text mark rather than a broken
 * image. No AI-generated logo is ever substituted.
 *
 * @see docs/07-branding-ui.md section 1
 */
export function BrandMark({
    mark = 'association',
    size = 'md',
    withName = false,
    className,
}: Props) {
    const [failed, setFailed] = useState(false);
    const { locale } = useTranslation();

    const group = mark === 'school' ? 'school' : 'organization';
    const path = useSetting<string>(`${group}.logo_path`);
    const nameBn = useSetting<string>(`${group}.name_bn`);
    const nameEn = useSetting<string>(`${group}.name_en`);

    const name = (locale === 'bn' ? nameBn : nameEn) ?? nameEn ?? '';
    const showImage = Boolean(path) && !failed;

    return (
        <span className={cn('flex items-center gap-3', className)}>
            {showImage ? (
                <img
                    src={`/storage/${path}`}
                    alt={name}
                    className={cn(SIZES[size], 'shrink-0 object-contain')}
                    onError={() => setFailed(true)}
                />
            ) : (
                <span
                    aria-hidden="true"
                    className={cn(
                        SIZES[size],
                        'bg-brand-green-800 text-background flex shrink-0 items-center justify-center rounded-full text-xs font-bold tracking-tight',
                    )}
                >
                    SSHS
                </span>
            )}

            {withName && (
                <span
                    lang={locale}
                    className="text-brand-green-900 dark:text-foreground truncate text-sm leading-tight font-semibold"
                >
                    {name}
                </span>
            )}
        </span>
    );
}
