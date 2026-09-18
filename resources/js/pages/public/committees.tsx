import { Mail, Phone, UserSquare } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-4xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.committees.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.committees.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-4xl space-y-8 px-4 py-10">
                    {committees.length === 0 ? (
                        <EmptyState
                            icon={UserSquare}
                            title={t('public.committees.title')}
                            description={t('public.committees.empty')}
                        />
                    ) : (
                        committees.map((committee) => (
                            <section key={committee.slug}>
                                <h2 className="text-brand-green-900 text-xl font-semibold">
                                    {committee.name}
                                </h2>

                                <p className="text-muted-foreground mt-1 text-sm">
                                    {committee.type_label}
                                    {committee.term
                                        ? ` · ${committee.term}`
                                        : ''}
                                </p>

                                {committee.description && (
                                    <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                                        {committee.description}
                                    </p>
                                )}

                                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
        <Card>
            <CardContent className="flex items-start gap-3 p-4">
                <Avatar className="size-12 shrink-0">
                    {person.photo_url && (
                        <AvatarImage src={person.photo_url} alt="" />
                    )}
                    <AvatarFallback>{getInitials(person.name)}</AvatarFallback>
                </Avatar>

                <div className="min-w-0">
                    <p className="truncate font-medium">{person.name}</p>
                    <p className="text-brand-green-800 truncate text-sm">
                        {person.role}
                    </p>

                    {person.designation && (
                        <p className="text-muted-foreground truncate text-xs">
                            {person.designation}
                        </p>
                    )}

                    <div className="text-muted-foreground mt-2 space-y-0.5 text-xs">
                        {person.contact_phone && (
                            <a
                                href={`tel:${person.contact_phone}`}
                                className="tabular-id flex items-center gap-1.5 hover:underline"
                            >
                                <Phone className="size-3" aria-hidden="true" />
                                {person.contact_phone}
                            </a>
                        )}
                        {person.contact_email && (
                            <a
                                href={`mailto:${person.contact_email}`}
                                className="flex items-center gap-1.5 break-all hover:underline"
                            >
                                <Mail
                                    className="size-3 shrink-0"
                                    aria-hidden="true"
                                />
                                {person.contact_email}
                            </a>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
