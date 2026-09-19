import { BadgeCheck, ShieldAlert, ShieldX } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
            {/* Ambient Hero / Content wrapper */}
            <div className="relative min-h-[75vh] overflow-hidden bg-gradient-to-b from-slate-50 via-teal-50/20 to-white py-16 sm:py-24 flex items-center justify-center">
                <div className="pointer-events-none absolute -left-20 top-0 h-80 w-80 rounded-full bg-teal-500/10 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />

                <div className="relative mx-auto w-full max-w-lg px-4">
                    {member === null ? (
                        <div className="rounded-3xl border border-red-200 bg-white/95 p-8 text-center shadow-xl backdrop-blur-md">
                            <div className="mx-auto flex size-16 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                                <ShieldX className="size-8" aria-hidden="true" />
                            </div>
                            <h1 className="mt-5 text-2xl font-bold text-slate-900">
                                {t('public.verify.not_found')}
                            </h1>
                            <p className="mt-2 text-sm text-slate-600 leading-relaxed">
                                {t('public.verify.not_found_note')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-3xl border border-teal-500/25 bg-white/95 shadow-2xl shadow-teal-900/10 backdrop-blur-md">
                            <div
                                className={
                                    member.is_verified
                                        ? 'bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-4 text-white'
                                        : 'bg-gradient-to-r from-red-600 to-rose-700 px-6 py-4 text-white'
                                }
                            >
                                <div className="flex items-center justify-between">
                                    <p className="flex items-center gap-2 font-bold text-sm tracking-wide">
                                        {member.is_verified ? (
                                            <BadgeCheck
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <ShieldAlert
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {member.is_verified
                                            ? t('public.verify.valid')
                                            : t('public.verify.invalid')}
                                    </p>
                                    <span className="text-xs font-semibold uppercase tracking-wider text-white/80">
                                        Official Credential
                                    </span>
                                </div>
                            </div>

                            <div className="p-6 sm:p-8">
                                <div className="flex items-start gap-5">
                                    <Avatar className="size-20 shrink-0 ring-4 ring-teal-500/20 shadow-md">
                                        {member.photo_url && (
                                            <AvatarImage
                                                src={member.photo_url}
                                                alt={member.full_name}
                                                className="object-cover"
                                            />
                                        )}
                                        <AvatarFallback className="bg-teal-50 text-teal-800 text-xl font-bold">
                                            {getInitials(member.full_name)}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div className="min-w-0 flex-1 space-y-2">
                                        <h1 className="text-xl sm:text-2xl font-bold text-slate-900">
                                            {member.full_name}
                                        </h1>

                                        <div className="flex flex-wrap gap-2">
                                            {member.batch && (
                                                <span className="inline-flex items-center rounded-full bg-teal-50 px-3 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200/60">
                                                    {member.batch}
                                                </span>
                                            )}
                                            {member.membership_no && (
                                                <span className="tabular-id inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-xs font-mono font-medium text-slate-700">
                                                    {member.membership_no}
                                                </span>
                                            )}
                                        </div>

                                        <p className="text-xs font-medium text-slate-500 pt-1">
                                            Status: <span className="text-slate-800 font-semibold">{member.status_label}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
