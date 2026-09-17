import { Printer } from 'lucide-react';
import { BrandMark } from '@/components/shared/brand-mark';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { useSetting } from '@/hooks/use-setting';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/format';
import MemberLayout from '@/layouts/member-layout';
import type { PublicMember } from '@/types/member';

type Props = {
    member: PublicMember;
    verify_url: string;
    /** An inline SVG data URI, rendered server-side. */
    qr: string;
};

/**
 * The digital membership card.
 *
 * It shows exactly what someone scanning it will see — the same six fields the
 * verification page returns. No surprises at the gate, and no temptation to
 * print more onto a card than the QR target discloses.
 *
 * Printing goes through the browser rather than dompdf: Bengali conjunct
 * shaping in dompdf is unreliable, and the school's name on this card is
 * Bangla.
 */
export default function Card({ member, verify_url, qr }: Props) {
    const { t } = useTranslation();
    const getInitials = useInitials();

    const schoolNameBn = useSetting<string>('school.name_bn');
    const schoolNameEn = useSetting<string>('school.name_en');

    return (
        <MemberLayout title={t('member.card.title')}>
            <div className="mx-auto max-w-md space-y-5">
                <div className="print:hidden">
                    <h1 className="text-2xl font-semibold">
                        {t('member.card.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('member.card.help')}
                    </p>
                </div>

                <div className="from-brand-green-900 to-brand-green-800 overflow-hidden rounded-2xl bg-gradient-to-br text-white shadow-lg">
                    <div className="flex items-center justify-between gap-3 border-b border-white/15 p-5">
                        <BrandMark size="sm" />
                        <Badge
                            variant={
                                member.is_verified ? 'default' : 'secondary'
                            }
                        >
                            {member.status_label}
                        </Badge>
                    </div>

                    <div className="flex items-start gap-4 p-5">
                        <Avatar className="size-20 shrink-0 ring-2 ring-white/20">
                            {member.photo_url && (
                                <AvatarImage
                                    src={member.photo_url}
                                    alt={member.full_name}
                                />
                            )}
                            <AvatarFallback className="text-brand-green-900 bg-white text-lg">
                                {getInitials(member.full_name)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="min-w-0 flex-1">
                            <p className="truncate text-lg font-semibold">
                                {member.full_name}
                            </p>

                            {member.batch && (
                                <p className="text-sm text-white/70">
                                    {member.batch}
                                </p>
                            )}

                            {member.membership_no && (
                                <p className="tabular-id mt-2 text-sm text-white/90">
                                    {member.membership_no}
                                </p>
                            )}

                            {member.verified_at && (
                                <p className="mt-1 text-xs text-white/50">
                                    {formatDate(member.verified_at)}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-4 border-t border-white/15 bg-white/5 p-5">
                        <img
                            src={qr}
                            alt=""
                            className="size-24 shrink-0 rounded bg-white p-1"
                            width={96}
                            height={96}
                        />

                        <div className="min-w-0 text-xs">
                            <p className="text-white/70">
                                {t('member.card.verify_hint')}
                            </p>
                            <p className="mt-1 truncate text-white/40">
                                {verify_url}
                            </p>

                            {/* The school, whose fifty years the Jubilee marks. */}
                            <div className="mt-3 space-y-0.5">
                                {schoolNameBn && (
                                    <p
                                        lang="bn"
                                        className="truncate text-white/80"
                                    >
                                        {schoolNameBn}
                                    </p>
                                )}
                                {schoolNameEn && (
                                    <p className="truncate text-white/40">
                                        {schoolNameEn}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    className="print:hidden"
                    onClick={() => window.print()}
                >
                    <Printer className="me-1 size-4" aria-hidden="true" />
                    {t('member.card.print')}
                </Button>
            </div>
        </MemberLayout>
    );
}
