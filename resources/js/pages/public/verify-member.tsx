import { BadgeCheck, ShieldX } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import type { PublicMember } from '@/types/member';

/**
 * The QR target from a digital membership card.
 *
 * Six fields. Nothing here is conditional on privacy settings, because the
 * server never sends more than this regardless of them.
 */
export default function VerifyMember({
    member,
}: {
    member: PublicMember | null;
}) {
    const { t } = useTranslation();
    const getInitials = useInitials();

    return (
        <PublicLayout title={t('public.verify.title')} indexable={false}>
            <div className="bg-brand-cream min-h-[60vh]">
                <div className="mx-auto max-w-lg px-4 py-16">
                    {member === null ? (
                        <div className="bg-card rounded-xl border p-8 text-center shadow-sm">
                            <ShieldX
                                className="text-muted-foreground mx-auto size-12"
                                aria-hidden="true"
                            />
                            <h1 className="mt-4 text-xl font-semibold">
                                {t('public.verify.not_found')}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {t('public.verify.not_found_note')}
                            </p>
                        </div>
                    ) : (
                        <div className="bg-card overflow-hidden rounded-xl border shadow-sm">
                            <div
                                className={
                                    member.is_verified
                                        ? 'bg-brand-green-800 px-6 py-4 text-white'
                                        : 'bg-brand-red-700 px-6 py-4 text-white'
                                }
                            >
                                <p className="flex items-center gap-2 font-medium">
                                    {member.is_verified ? (
                                        <BadgeCheck
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        <ShieldX
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    )}
                                    {member.is_verified
                                        ? t('public.verify.valid')
                                        : t('public.verify.invalid')}
                                </p>
                            </div>

                            <div className="flex gap-5 p-6">
                                <Avatar className="size-20 shrink-0">
                                    {member.photo_url && (
                                        <AvatarImage
                                            src={member.photo_url}
                                            alt=""
                                        />
                                    )}
                                    <AvatarFallback className="text-lg">
                                        {getInitials(member.full_name)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="min-w-0 space-y-2">
                                    <h1 className="text-xl font-semibold">
                                        {member.full_name}
                                    </h1>

                                    {member.full_name_bn && (
                                        <p
                                            lang="bn"
                                            className="text-muted-foreground"
                                        >
                                            {member.full_name_bn}
                                        </p>
                                    )}

                                    <div className="flex flex-wrap gap-2">
                                        {member.batch && (
                                            <Badge variant="secondary">
                                                {member.batch}
                                            </Badge>
                                        )}
                                        {member.membership_no && (
                                            <Badge
                                                variant="outline"
                                                className="tabular-id"
                                            >
                                                {member.membership_no}
                                            </Badge>
                                        )}
                                    </div>

                                    <p className="text-muted-foreground text-sm">
                                        {member.status_label}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
