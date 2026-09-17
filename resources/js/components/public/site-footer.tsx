import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import { BrandMark } from '@/components/shared/brand-mark';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
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
        <footer className="bg-brand-green-900 mt-auto text-white/80">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div className="space-y-4">
                    <BrandMark size="md" />

                    {/* The school's own name, in its own script, with the
                        transliteration under it. */}
                    <div className="space-y-0.5">
                        {schoolNameBn && (
                            <p
                                lang="bn"
                                className="text-sm leading-relaxed text-white"
                            >
                                {schoolNameBn}
                            </p>
                        )}
                        {schoolNameEn && (
                            <p className="text-xs leading-relaxed text-white/60">
                                {schoolNameEn}
                            </p>
                        )}
                    </div>

                    {/* Both founding years, stated separately. The fifty years
                        are the school's; the association organises. */}
                    <dl className="space-y-1 text-xs text-white/60">
                        {schoolEstablished && (
                            <div className="flex gap-2">
                                <dt>{t('public.nav.about_school')}:</dt>
                                <dd>{formatNumber(schoolEstablished)}</dd>
                            </div>
                        )}
                        {orgEstablished && (
                            <div className="flex gap-2">
                                <dt>{t('public.nav.about_association')}:</dt>
                                <dd>{formatNumber(orgEstablished)}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                <nav aria-labelledby="footer-links">
                    <h2
                        id="footer-links"
                        className="mb-4 text-sm font-semibold text-white"
                    >
                        {t('public.footer.quick_links')}
                    </h2>
                    <ul className="space-y-2 text-sm">
                        {quickLinks.map((link) => (
                            <li key={link.key}>
                                <Link
                                    href={link.href}
                                    className="transition-colors hover:text-white"
                                >
                                    {t(`public.nav.${link.key}`)}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div>
                    <h2 className="mb-4 text-sm font-semibold text-white">
                        {t('public.footer.contact')}
                    </h2>
                    <ul className="space-y-3 text-sm">
                        {address && (
                            <li className="flex gap-2">
                                <MapPin
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>{address}</span>
                            </li>
                        )}
                        {phone && (
                            <li className="flex gap-2">
                                <Phone
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                {/* Identifiers stay in Latin digits so they can
                                    be dialled and read aloud reliably. */}
                                <a href={`tel:${phone}`} className="tabular-id">
                                    {phone}
                                </a>
                            </li>
                        )}
                        {email && (
                            <li className="flex gap-2">
                                <Mail
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <a
                                    href={`mailto:${email}`}
                                    className="break-all"
                                >
                                    {email}
                                </a>
                            </li>
                        )}
                    </ul>
                </div>

                {legalLinks.length > 0 && (
                    <div>
                        <h2 className="mb-4 text-sm font-semibold text-white">
                            {t('public.nav.about')}
                        </h2>
                        <ul className="space-y-2 text-sm">
                            {legalLinks.map((link) => (
                                <li key={link.key}>
                                    <Link
                                        href={link.href}
                                        className="transition-colors hover:text-white"
                                    >
                                        {t(`public.footer.${link.key}`)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            <div className="border-t border-white/10">
                <p className="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-white/50">
                    © {formatNumber(year)} — {t('public.hero.organisation')}.{' '}
                    {t('public.footer.rights')}
                </p>
            </div>
        </footer>
    );
}
