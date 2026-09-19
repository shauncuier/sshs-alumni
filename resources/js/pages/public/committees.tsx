import { Mail, Phone, Users, UserSquare } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';

type Person = {
    name: string;
    role: string;
    designation: string | null;
    photo_url: string | null;
    contact_email: string | null;
    contact_phone: string | null;
};

type Committee = {
    slug: string;
    name: string;
    type_label: string;
    description: string | null;
    term: string | null;
    members: Person[];
};

type Props = { committees: Committee[] };

/**
 * Who runs the association.
 *
 * Contact details ARE shown here, unlike the directory. A committee member's
 * role is to be contactable — that is a different thing from an ordinary
 * member's phone number, which stays hidden unless they say otherwise.
 */
export default function Committees({ committees }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.committees.title')}
            description={t('public.committees.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Users className="size-3.5" />
                        Executive & Advisory
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.committees.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.committees.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/20 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-5xl space-y-12 px-4 sm:px-6 lg:px-8">
                    {committees.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={UserSquare}
                                title={t('public.committees.title')}
                                description={t('public.committees.empty')}
                            />
                        </div>
                    ) : (
                        committees.map((committee) => (
                            <section
                                key={committee.slug}
                                className="glass-panel-light rounded-3xl p-6 sm:p-8 shadow-sm border border-teal-500/15"
                            >
                                <div className="border-b border-slate-100 pb-5">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <h2 className="text-xl sm:text-2xl font-bold text-slate-900">
                                            {committee.name}
                                        </h2>
                                        <div className="flex items-center gap-2">
                                            <span className="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 border border-teal-200/60">
                                                {committee.type_label}
                                            </span>
                                            {committee.term && (
                                                <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                    {committee.term}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {committee.description && (
                                        <p className="mt-3 text-sm text-slate-600 leading-relaxed max-w-3xl">
                                            {committee.description}
                                        </p>
                                    )}
                                </div>

                                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {committee.members.map((person) => (
                                        <PersonCard
                                            key={`${committee.slug}-${person.name}`}
                                            person={person}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}

function PersonCard({ person }: { person: Person }) {
    const getInitials = useInitials();

    return (
        <div className="group rounded-2xl border border-teal-500/10 bg-white/90 p-4 shadow-sm backdrop-blur-md transition-all duration-300 hover:border-teal-500/30 hover:shadow-md hover:-translate-y-0.5">
            <div className="flex items-start gap-3.5">
                <Avatar className="size-13 shrink-0 ring-2 ring-teal-500/20 transition group-hover:ring-teal-500/40">
                    {person.photo_url && (
                        <AvatarImage src={person.photo_url} alt={person.name} />
                    )}
                    <AvatarFallback className="bg-teal-50 text-teal-800 font-semibold text-sm">
                        {getInitials(person.name)}
                    </AvatarFallback>
                </Avatar>

                <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold text-slate-900 group-hover:text-teal-900 transition">
                        {person.name}
                    </p>
                    <p className="truncate text-xs font-semibold text-teal-700">
                        {person.role}
                    </p>

                    {person.designation && (
                        <p className="mt-0.5 truncate text-xs text-slate-500">
                            {person.designation}
                        </p>
                    )}

                    {(person.contact_phone || person.contact_email) && (
                        <div className="mt-3 space-y-1 border-t border-slate-100 pt-2 text-xs text-slate-500">
                            {person.contact_phone && (
                                <a
                                    href={`tel:${person.contact_phone}`}
                                    className="tabular-id flex items-center gap-1.5 transition hover:text-teal-600"
                                >
                                    <Phone className="size-3 text-teal-600/70" aria-hidden="true" />
                                    <span>{person.contact_phone}</span>
                                </a>
                            )}
                            {person.contact_email && (
                                <a
                                    href={`mailto:${person.contact_email}`}
                                    className="flex items-center gap-1.5 break-all transition hover:text-teal-600"
                                >
                                    <Mail
                                        className="size-3 shrink-0 text-teal-600/70"
                                        aria-hidden="true"
                                    />
                                    <span>{person.contact_email}</span>
                                </a>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
