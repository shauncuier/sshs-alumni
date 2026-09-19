import { Megaphone, Paperclip, Pin } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';
import { formatDate } from '@/lib/format';
import type { AnnouncementCard } from '@/types/content';
import type { Paginated } from '@/types/member';

type Props = {
    announcements: Paginated<AnnouncementCard>;
};

/**
 * Announcements and notices.
 *
 * The list narrows itself to the reader on the server: a stranger sees the
 * public ones, a signed-in member also sees members' notices and their own
 * batch's. One page, one route, no "members only" section that leaks its
 * headings to everybody.
 */
export default function Announcements({ announcements }: Props) {
    const { t } = useTranslation();

    return (
        <PublicLayout
            title={t('public.announcements.title')}
            description={t('public.announcements.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <Megaphone className="size-3.5" />
                        Official Notices
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.announcements.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.announcements.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                    {announcements.data.length === 0 ? (
                        <div className="glass-panel-light p-8 rounded-2xl">
                            <EmptyState
                                icon={Megaphone}
                                title={t('public.announcements.title')}
                                description={t('public.announcements.empty')}
                            />
                        </div>
                    ) : (
                        announcements.data.map((announcement) => (
                            <div
                                key={announcement.id}
                                className="group rounded-3xl border border-teal-500/15 bg-white/90 p-6 sm:p-7 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-0.5 hover:border-teal-500/35 hover:shadow-lg"
                            >
                                <div className="space-y-3">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {announcement.is_pinned && (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 border border-amber-300/70">
                                                <Pin className="size-3" aria-hidden="true" />
                                                {t('public.announcements.pinned')}
                                            </span>
                                        )}

                                        {announcement.kind_label && (
                                            <span className="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200/60">
                                                {announcement.kind_label}
                                            </span>
                                        )}

                                        {announcement.level !== 'info' && (
                                            <span
                                                className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold ${
                                                    announcement.level === 'urgent'
                                                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                                        : 'bg-slate-100 text-slate-700'
                                                }`}
                                            >
                                                {announcement.level_label}
                                            </span>
                                        )}

                                        {announcement.batch && (
                                            <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                                {t(
                                                    'public.announcements.batch_only',
                                                    {
                                                        batch: announcement.batch,
                                                    },
                                                )}
                                            </span>
                                        )}
                                    </div>

                                    <h2 className="text-xl font-bold text-slate-900 group-hover:text-teal-900 transition">
                                        {announcement.title}
                                    </h2>

                                    <p className="text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">
                                        {announcement.body}
                                    </p>

                                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-400">
                                        <span>
                                            {formatDate(announcement.starts_at)}
                                            {announcement.ends_at
                                                ? ` · ${t('public.announcements.until', { date: formatDate(announcement.ends_at) })}`
                                                : ''}
                                        </span>

                                        {announcement.attachment_url && (
                                            <a
                                                href={announcement.attachment_url}
                                                className="inline-flex items-center gap-1.5 font-semibold text-teal-700 hover:text-teal-800 hover:underline"
                                            >
                                                <Paperclip
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t(
                                                    'public.announcements.attachment',
                                                )}
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}

                    <Pagination meta={announcements.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
