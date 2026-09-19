import { useState } from 'react';
import { useSetting } from '@/hooks/use-setting';
import { cn } from '@/lib/utils';

type Props = {
    /** `association` is the platform's own identity; `school` appears on school pages. */
    mark?: 'association' | 'school';
    size?: 'sm' | 'md' | 'lg';
    /** Show the organisation name beside the mark. */
    withName?: boolean;
    /** Text color theme for the name label: 'auto' | 'light' | 'dark' */
    theme?: 'light' | 'dark' | 'auto';
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
 * The site reads in English, but the organisation's and the school's own names
 * stay in Bangla: those are their names, not copy to be translated. The
 * transliteration follows as a secondary line, and `lang="bn"` on the Bangla
 * node is what makes the browser apply Noto Sans Bengali and shape conjuncts
 * correctly.
 *
 * @see docs/07-branding-ui.md section 1
 */
export function BrandMark({
    mark = 'association',
    size = 'md',
    withName = false,
    theme = 'auto',
    className,
}: Props) {
    const [failed, setFailed] = useState(false);

    const group = mark === 'school' ? 'school' : 'organization';
    const path = useSetting<string>(`${group}.logo_path`);
    const nameBn = useSetting<string>(`${group}.name_bn`);
    const nameEn = useSetting<string>(`${group}.name_en`);

    // Determine the image src: use custom storage path if uploaded, or fallback to the authentic bundled logo
    const defaultSrc = mark === 'school' ? '/brand/logo-school.png' : '/brand/logo-association.png';
    const imageSrc = path
        ? path.startsWith('brand/') || path.startsWith('/')
            ? path.startsWith('/') ? path : `/${path}`
            : `/storage/${path}`
        : defaultSrc;

    const showImage = !failed;

    const nameBnColor =
        theme === 'dark'
            ? 'text-white font-bold'
            : theme === 'light'
              ? 'text-slate-900 font-bold'
              : 'text-brand-green-900 dark:text-foreground font-semibold';

    const nameEnColor =
        theme === 'dark'
            ? 'text-slate-300 font-medium'
            : theme === 'light'
              ? 'text-slate-500'
              : 'text-muted-foreground';

    return (
        <span className={cn('flex items-center gap-3', className)}>
            {showImage ? (
                <img
                    src={imageSrc}
                    alt={nameEn ?? (mark === 'school' ? 'SSHS School Crest' : 'SSHS Alumni Crest')}
                    className={cn(SIZES[size], 'shrink-0 object-contain drop-shadow-sm')}
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
                <span className="min-w-0 leading-tight">
                    {nameBn && (
                        <span
                            lang="bn"
                            className={cn('block truncate text-sm', nameBnColor)}
                        >
                            {nameBn}
                        </span>
                    )}
                    {nameEn && (
                        <span className={cn('block truncate text-xs', nameEnColor)}>
                            {nameEn}
                        </span>
                    )}
                </span>
            )}
        </span>
    );
}
