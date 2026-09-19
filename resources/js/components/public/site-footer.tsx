import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import { BrandMark } from '@/components/shared/brand-mark';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatYear } from '@/lib/format';
import type { PublicNavItem } from '@/types/shared';

export function SiteFooter() {
    const { t } = useTranslation();
    const page = usePage();

    const nav = page.props.nav as
        | { publicFooter?: PublicNavItem[]; publicLegal?: PublicNavItem[] }
        | undefined;
    const quickLinks = nav?.publicFooter ?? [];
    // Privacy and terms are CMS pages. Until the CMS ships they do not exist,
    // and the column is dropped rather than linking to a 404.
    const legalLinks = nav?.publicLegal ?? [];

    const orgEstablished = useSetting<number>('organization.established');
    const schoolEstablished = useSetting<number>('school.established');
    const schoolNameBn = useSetting<string>('school.name_bn');
    const schoolNameEn = useSetting<string>('school.name_en');
    const address = useSetting<string>('school.address');
    const phone = useSetting<string>('contact.phone');
    const email = useSetting<string>('contact.email');

    const year = new Date().getFullYear();

    return (
        <footer className="relative mt-auto overflow-hidden bg-gradient-to-b from-[#080d1e] via-[#0b1329] to-[#040711] text-slate-300">
            {/* Ambient Background Glows */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-24 left-1/4 h-72 w-72 rounded-full bg-teal-500/10 blur-3xl"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute bottom-0 right-10 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"
            />

            <div className="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:grid-cols-2 lg:grid-cols-4">
                <div className="space-y-4">
                    <BrandMark size="md" />

                    {/* The school's own name, in its own script */}
                    <div className="space-y-0.5">
                        {schoolNameBn && (
                            <p
                                lang="bn"
                                className="text-sm font-medium leading-relaxed text-white"
                            >
                                {schoolNameBn}
                            </p>
                        )}
                        {schoolNameEn && (
                            <p className="text-xs leading-relaxed text-slate-400">
                                {schoolNameEn}
                            </p>
                        )}
                    </div>

                    {/* Both founding years */}
                    <dl className="space-y-1.5 pt-2 text-xs text-slate-400">
                        {schoolEstablished && (
                            <div className="flex items-center gap-2">
                                <dt className="font-medium text-slate-300">{t('public.nav.about_school')}:</dt>
                                <dd className="font-semibold text-amber-400">{formatYear(schoolEstablished)}</dd>
                            </div>
                        )}
                        {orgEstablished && (
                            <div className="flex items-center gap-2">
                                <dt className="font-medium text-slate-300">{t('public.nav.about_association')}:</dt>
                                <dd className="font-semibold text-teal-400">{formatYear(orgEstablished)}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                <nav aria-labelledby="footer-links">
                    <h2
                        id="footer-links"
                        className="mb-4 text-xs font-bold tracking-wider text-white uppercase"
                    >
                        {t('public.footer.quick_links')}
                    </h2>
                    <ul className="space-y-2.5 text-sm">
                        {quickLinks.map((link) => (
                            <li key={link.key}>
                                <Link
                                    href={link.href}
                                    className="transition-colors hover:text-teal-300"
                                >
                                    {t(`public.nav.${link.key}`)}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div>
                    <h2 className="mb-4 text-xs font-bold tracking-wider text-white uppercase">
                        {t('public.footer.contact')}
                    </h2>
                    <ul className="space-y-3 text-sm">
                        {address && (
                            <li className="flex items-start gap-2.5">
                                <MapPin
                                    className="mt-0.5 size-4 shrink-0 text-teal-400"
                                    aria-hidden="true"
                                />
                                <span className="leading-relaxed text-slate-300">{address}</span>
                            </li>
                        )}
                        {phone && (
                            <li className="flex items-center gap-2.5">
                                <Phone
                                    className="size-4 shrink-0 text-teal-400"
                                    aria-hidden="true"
                                />
                                <a href={`tel:${phone}`} className="tabular-id transition-colors hover:text-teal-300">
                                    {phone}
                                </a>
                            </li>
                        )}
                        {email && (
                            <li className="flex items-center gap-2.5">
                                <Mail
                                    className="size-4 shrink-0 text-teal-400"
                                    aria-hidden="true"
                                />
                                <a
                                    href={`mailto:${email}`}
                                    className="break-all transition-colors hover:text-teal-300"
                                >
                                    {email}
                                </a>
                            </li>
                        )}
                    </ul>
                </div>

                {legalLinks.length > 0 && (
                    <div>
                        <h2 className="mb-4 text-xs font-bold tracking-wider text-white uppercase">
                            {t('public.nav.about')}
                        </h2>
                        <ul className="space-y-2.5 text-sm">
                            {legalLinks.map((link) => (
                                <li key={link.key}>
                                    <Link
                                        href={link.href}
                                        className="transition-colors hover:text-teal-300"
                                    >
                                        {t(`public.footer.${link.key}`)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            <div className="relative border-t border-white/10 bg-black/20">
                <p className="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-slate-400">
                    © {formatYear(year)} — {t('public.hero.organisation')}.{' '}
                    {t('public.footer.rights')}
                </p>
            </div>
        </footer>
    );
}
