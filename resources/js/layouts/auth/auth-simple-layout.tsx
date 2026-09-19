import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] p-6 text-white md:p-10">
            {/* Ambient Background Glows */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-28 left-1/2 -translate-x-1/2 h-96 w-[36rem] rounded-full bg-gradient-to-tr from-teal-500/15 via-cyan-500/15 to-transparent blur-3xl"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute bottom-10 right-1/4 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"
            />

            <div className="relative w-full max-w-md">
                <div className="glass-panel-dark relative overflow-hidden rounded-3xl border border-teal-500/20 p-8 shadow-2xl backdrop-blur-2xl sm:p-10">
                    {/* Top Decorative Gradient */}
                    <div
                        aria-hidden="true"
                        className="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-amber-400 via-teal-500 to-cyan-500"
                    />

                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="group flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="flex size-16 items-center justify-center rounded-2xl border border-teal-400/30 bg-white/10 p-2 shadow-lg shadow-teal-950/40 backdrop-blur-md transition-transform duration-200 group-hover:scale-105">
                                <AppLogoIcon className="size-12 drop-shadow-md" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-1.5 text-center">
                            <h1 className="bg-gradient-to-r from-white via-slate-100 to-slate-200 bg-clip-text text-2xl font-extrabold text-transparent sm:text-3xl">
                                {title}
                            </h1>
                            <p className="text-center text-xs text-slate-300 sm:text-sm">
                                {description}
                            </p>
                        </div>
                    </div>

                    <div className="mt-8 text-slate-100">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
