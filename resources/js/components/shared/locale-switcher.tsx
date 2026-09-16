import { Link, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/hooks/use-translation';
import type { Locale } from '@/types/shared';

type Props = {
    /** `icon` for tight headers, `full` where there is room for the label. */
    variant?: 'icon' | 'full';
    className?: string;
};

/**
 * Language switcher.
 *
 * Each locale is listed under its OWN native name — a picker that says
 * "Bengali" in English is useless to a Bangla-first reader, who is the
 * majority of this audience.
 *
 * @see docs/06-localization.md
 */
export function LocaleSwitcher({ variant = 'icon', className }: Props) {
    const page = usePage();
    const { locale } = useTranslation();
    const locales = (page.props.locales ?? {}) as Record<Locale, string>;
    const entries = Object.entries(locales) as [Locale, string][];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size={variant === 'icon' ? 'icon' : 'sm'}
                    className={className}
                    aria-label={
                        // Localised, because the control itself must be
                        // understandable in the language you are switching FROM.
                        locale === 'bn' ? 'ভাষা পরিবর্তন' : 'Change language'
                    }
                >
                    <Languages className="size-4" aria-hidden="true" />
                    {variant === 'full' && (
                        <span className="ms-2">{locales[locale]}</span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end">
                {entries.map(([code, label]) => (
                    <DropdownMenuItem key={code} asChild>
                        <Link
                            href={`/locale/${code}`}
                            preserveScroll
                            // The active language is still listed, so the menu
                            // shows which one is current rather than hiding it.
                            className={
                                code === locale ? 'font-semibold' : undefined
                            }
                            // Each label renders in its own script.
                            lang={code}
                        >
                            {label}
                        </Link>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
