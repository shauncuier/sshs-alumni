import { Link } from '@inertiajs/react';
import { ArrowLeft, Briefcase, Mail, MapPin, Phone } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import MemberLayout from '@/layouts/member-layout';
import type { DirectoryMember } from '@/types/member';

/**
 * A single directory profile.
 *
 * Every optional field below is optional because the SERVER decides whether to
 * send it. There is no client-side privacy logic here, and there must never be.
 */
export default function DirectoryShow({ member }: { member: DirectoryMember }) {
    const { t } = useTranslation();
    const getInitials = useInitials();

    const location = [member.city, member.district, member.country]
        .filter(Boolean)
        .join(', ');

    return (
        <MemberLayout title={member.full_name}>
            <div className="space-y-6">
                <Button asChild variant="ghost" size="sm">
                    <Link href="/directory">
                        <ArrowLeft
                            className="me-1 size-4 rtl:rotate-180"
                            aria-hidden="true"
                        />
                        {t('member.nav.directory')}
                    </Link>
                </Button>

                <Card>
                    <CardContent className="flex flex-col gap-5 p-6 sm:flex-row">
                        <Avatar className="size-24 shrink-0">
                            {member.photo_url && (
                                <AvatarImage src={member.photo_url} alt="" />
                            )}
                            <AvatarFallback className="text-xl">
                                {getInitials(member.full_name)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="min-w-0 flex-1 space-y-3">
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    {member.full_name}
                                </h1>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {member.batch && (
                                    <Badge variant="secondary">
                                        {member.batch}
                                    </Badge>
                                )}
                                <Badge variant="outline">
                                    {member.relation_label}
                                </Badge>
                                {member.membership_no && (
                                    <Badge
                                        variant="outline"
                                        className="tabular-id"
                                    >
                                        {member.membership_no}
                                    </Badge>
                                )}
                            </div>

                            {member.bio && (
                                <p className="text-sm leading-relaxed">
                                    {member.bio}
                                </p>
                            )}

                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                {member.occupation && (
                                    <Detail icon={Briefcase}>
                                        {member.occupation}
                                        {member.organization
                                            ? ` · ${member.organization}`
                                            : ''}
                                    </Detail>
                                )}

                                {location && (
                                    <Detail icon={MapPin}>{location}</Detail>
                                )}

                                {member.mobile && (
                                    <Detail icon={Phone}>
                                        <a
                                            href={`tel:${member.mobile}`}
                                            className="tabular-id hover:underline"
                                        >
                                            {member.mobile}
                                        </a>
                                    </Detail>
                                )}

                                {member.email && (
                                    <Detail icon={Mail}>
                                        <a
                                            href={`mailto:${member.email}`}
                                            className="break-all hover:underline"
                                        >
                                            {member.email}
                                        </a>
                                    </Detail>
                                )}
                            </dl>

                            {member.links && member.links.length > 0 && (
                                <div className="flex flex-wrap gap-2 pt-1">
                                    {member.links.map((link) => (
                                        <Button
                                            key={link.url}
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <a
                                                href={link.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                {link.label}
                                            </a>
                                        </Button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}

function Detail({
    icon: Icon,
    children,
}: {
    icon: ComponentType<{ className?: string }>;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center gap-2">
            <Icon
                className="text-muted-foreground size-4 shrink-0"
                aria-hidden="true"
            />
            <span className="min-w-0 truncate">{children}</span>
        </div>
    );
}
