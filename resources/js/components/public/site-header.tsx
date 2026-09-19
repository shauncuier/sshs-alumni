import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { BrandMark } from '@/components/shared/brand-mark';
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
    const { t } = useTranslation();
    const page = usePage();
    const [open, setOpen] = useState(false);

    // Server-built from routes that exist, so an unshipped phase never
    // appears in the menu.
    const nav = page.props.nav as
        | { publicPrimary?: PublicNavItem[] }
        | undefined;
    const links = nav?.publicPrimary ?? [];

    const auth = page.props.auth as {
        user?: unknown;
        roles?: string[];
        permissions?: string[];
    } | undefined;
    const isAuthenticated = Boolean(auth?.user);
    const isSuperAdmin = Boolean(auth?.roles?.includes('Super Admin'));
    const canAccessAdmin = Boolean(isSuperAdmin || auth?.permissions?.includes('admin.access'));
    const current = page.url;

    const isActive = (href: string) =>
        href === '/' ? current === '/' : current.startsWith(href);

    return (
        <header className="sticky top-0 z-50 w-full border-b border-teal-500/20 bg-[#060a17]/85 shadow-lg shadow-black/25 backdrop-blur-2xl transition-all">
            {/* Top Micro-Accent Highlight Line */}
            <div
                aria-hidden="true"
                className="absolute top-0 left-0 h-[2px] w-full bg-gradient-to-r from-teal-500/40 via-cyan-400/80 to-amber-400/40"
            />

            <div className="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6">
                <Link
                    href="/"
                    className="group flex shrink-0 items-center transition-opacity hover:opacity-95"
                    aria-label={t('public.nav.home')}
                >
                    <BrandMark size="sm" withName theme="dark" className="max-w-[16rem]" />
                </Link>

                <nav
                    className="ms-auto hidden items-center gap-1 xl:gap-1.5 lg:flex"
                    aria-label={t('public.nav.home')}
                >
                    {links.map((link) => {
                        const active = isActive(link.href);
                        return (
                            <Link
                                key={link.key}
                                href={link.href}
                                className={cn(
                                    'relative rounded-xl px-3 py-1.5 text-xs font-semibold tracking-wide transition-all duration-200',
                                    active
                                        ? 'bg-teal-500/20 text-cyan-300 shadow-inner ring-1 ring-teal-400/30'
                                        : 'text-slate-300 hover:bg-white/5 hover:text-white',
                                )}
                            >
                                {t(`public.nav.${link.key}`)}
                                {active && (
                                    <span
                                        aria-hidden="true"
                                        className="absolute -bottom-1 left-1/2 -translate-x-1/2 h-0.5 w-4 rounded-full bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,0.8)]"
                                    />
                                )}
                            </Link>
                        );
                    })}
                </nav>

                <div className="ms-auto flex items-center gap-2 lg:ms-0">
                    {isAuthenticated ? (
                        <>
                            {canAccessAdmin && (
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="hidden rounded-xl border-amber-400/40 bg-amber-500/10 text-xs font-semibold text-amber-300 hover:bg-amber-500/20 hover:text-amber-200 sm:inline-flex"
                                >
                                    <Link href="/admin/dashboard">
                                        Admin Panel
                                    </Link>
                                </Button>
                            )}
                            <Button
                                asChild
                                size="sm"
                                className="rounded-xl bg-gradient-to-r from-teal-500 to-cyan-500 px-4 text-xs font-bold text-slate-950 shadow-md shadow-teal-500/25 transition-all hover:from-teal-400 hover:to-cyan-400 hover:shadow-teal-400/35"
                            >
                                <Link href="/dashboard">
                                    {t('admin.nav.dashboard')}
                                </Link>
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="hidden rounded-xl text-xs font-semibold text-slate-300 hover:bg-white/10 hover:text-white sm:inline-flex"
                            >
                                <Link href="/login">
                                    {t('common.actions.login')}
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="sm"
                                className="rounded-xl bg-gradient-to-r from-teal-500 via-cyan-500 to-teal-400 px-4.5 text-xs font-bold text-slate-950 shadow-md shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] hover:from-teal-400 hover:via-cyan-400 hover:to-teal-300 hover:shadow-lg hover:shadow-cyan-400/40"
                            >
                                <Link href="/join">{t('public.nav.join')}</Link>
                            </Button>
                        </>
                    )}

                    {/* Drawer navigation below lg */}
                    <Sheet open={open} onOpenChange={setOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="rounded-xl text-slate-300 hover:bg-white/10 hover:text-white lg:hidden"
                                aria-label={t('public.nav.home')}
                            >
                                <Menu className="size-5" aria-hidden="true" />
                            </Button>
                        </SheetTrigger>

                        <SheetContent
                            side="right"
                            className="w-80 border-s border-teal-500/20 bg-[#070c1b]/95 p-6 text-white backdrop-blur-3xl"
                        >
                            <SheetHeader className="border-b border-white/10 pb-4 text-start">
                                <SheetTitle className="text-start">
                                    <BrandMark size="sm" withName theme="dark" />
                                </SheetTitle>
                            </SheetHeader>

                            <nav className="mt-6 flex flex-col gap-1.5">
                                {links.map((link) => {
                                    const active = isActive(link.href);
                                    return (
                                        <Link
                                            key={link.key}
                                            href={link.href}
                                            onClick={() => setOpen(false)}
                                            className={cn(
                                                'rounded-xl px-4 py-2.5 text-sm font-semibold transition-all',
                                                active
                                                    ? 'bg-teal-500/20 text-cyan-300 ring-1 ring-teal-400/30'
                                                    : 'text-slate-300 hover:bg-white/5 hover:text-white',
                                            )}
                                        >
                                            {t(`public.nav.${link.key}`)}
                                        </Link>
                                    );
                                })}

                                {canAccessAdmin && (
                                    <Link
                                        href="/admin/dashboard"
                                        onClick={() => setOpen(false)}
                                        className="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-300 transition-all hover:bg-amber-500/20"
                                    >
                                        Admin Panel
                                    </Link>
                                )}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
