import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { BrandMark } from '@/components/shared/brand-mark';
import { LocaleSwitcher } from '@/components/shared/locale-switcher';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { PublicNavItem } from '@/types/shared';

export function SiteHeader() {
    const { t, locale } = useTranslation();
    const page = usePage();
    const [open, setOpen] = useState(false);

    // Server-built from routes that exist, so an unshipped phase never
    // appears in the menu.
    const nav = page.props.nav as
        | { publicPrimary?: PublicNavItem[] }
        | undefined;
    const links = nav?.publicPrimary ?? [];

    const auth = page.props.auth as { user?: unknown } | undefined;
    const isAuthenticated = Boolean(auth?.user);
    const current = page.url;

    const isActive = (href: string) =>
        href === '/' ? current === '/' : current.startsWith(href);

    return (
        <header className="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky top-0 z-50 w-full border-b backdrop-blur">
            <div className="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4">
                <Link
                    href="/"
                    className="flex shrink-0 items-center"
                    aria-label={t('public.nav.home')}
                >
                    <BrandMark size="sm" withName className="max-w-[16rem]" />
                </Link>

                <nav
                    className="ms-auto hidden items-center gap-1 lg:flex"
                    aria-label={t('public.nav.home')}
                >
                    {links.map((link) => (
                        <Link
                            key={link.key}
                            href={link.href}
                            lang={locale}
                            className={cn(
                                'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                isActive(link.href)
                                    ? 'text-brand-green-800 bg-brand-green-100'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                            )}
                        >
                            {t(`public.nav.${link.key}`)}
                        </Link>
                    ))}
                </nav>

                <div className="ms-auto flex items-center gap-2 lg:ms-0">
                    <LocaleSwitcher />

                    {isAuthenticated ? (
                        <Button asChild size="sm">
                            <Link href="/dashboard">
                                {t('admin.nav.dashboard')}
                            </Link>
                        </Button>
                    ) : (
                        <>
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="hidden sm:inline-flex"
                            >
                                <Link href="/login">
                                    {t('common.actions.login')}
                                </Link>
                            </Button>
                            <Button asChild size="sm">
                                <Link href="/join">{t('public.nav.join')}</Link>
                            </Button>
                        </>
                    )}

                    {/* Drawer navigation below lg — the full menu does not fit
                        on a phone, which is most of this audience. */}
                    <Sheet open={open} onOpenChange={setOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="lg:hidden"
                                aria-label={t('public.nav.home')}
                            >
                                <Menu className="size-5" aria-hidden="true" />
                            </Button>
                        </SheetTrigger>

                        <SheetContent side="right" className="w-72">
                            <SheetHeader>
                                <SheetTitle className="text-start">
                                    <BrandMark size="sm" withName />
                                </SheetTitle>
                            </SheetHeader>

                            <nav className="mt-6 flex flex-col gap-1 px-4">
                                {links.map((link) => (
                                    <Link
                                        key={link.key}
                                        href={link.href}
                                        lang={locale}
                                        onClick={() => setOpen(false)}
                                        className={cn(
                                            'rounded-md px-3 py-2.5 text-sm font-medium',
                                            isActive(link.href)
                                                ? 'text-brand-green-800 bg-brand-green-100'
                                                : 'text-muted-foreground hover:bg-accent',
                                        )}
                                    >
                                        {t(`public.nav.${link.key}`)}
                                    </Link>
                                ))}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
