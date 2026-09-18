import { Megaphone, Paperclip, Pin } from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-3xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.announcements.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.announcements.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto max-w-3xl space-y-4 px-4 py-10">
                    {announcements.data.length === 0 ? (
                        <EmptyState
                            icon={Megaphone}
                            title={t('public.announcements.title')}
                            description={t('public.announcements.empty')}
                        />
                    ) : (
                        announcements.data.map((announcement) => (
                            <Card key={announcement.id}>
                                <CardContent className="space-y-2 pt-6">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {announcement.is_pinned && (
                                            <Badge
                                                variant="secondary"
                                                className="gap-1"
                                            >
                                                <Pin
                                                    className="size-3"
                                                    aria-hidden="true"
                                                />
                                                {t(
                                                    'public.announcements.pinned',
                                                )}
                                            </Badge>
                                        )}

                                        {announcement.kind_label && (
                                            <Badge variant="outline">
                                                {announcement.kind_label}
                                            </Badge>
                                        )}

                                        {announcement.level !== 'info' && (
                                            <Badge
                                                variant={
                                                    announcement.level ===
                                                    'urgent'
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {announcement.level_label}
                                            </Badge>
                                        )}

                                        {announcement.batch && (
                                            <Badge variant="outline">
                                                {t(
                                                    'public.announcements.batch_only',
                                                    {
                                                        batch: announcement.batch,
                                                    },
                                                )}
                                            </Badge>
                                        )}
                                    </div>

                                    <h2 className="font-semibold">
                                        {announcement.title}
                                    </h2>

                                    <p className="text-muted-foreground text-sm leading-relaxed whitespace-pre-wrap">
                                        {announcement.body}
                                    </p>

                                    <p className="text-muted-foreground text-xs">
                                        {formatDate(announcement.starts_at)}
                                        {announcement.ends_at
                                            ? ` · ${t('public.announcements.until', { date: formatDate(announcement.ends_at) })}`
                                            : ''}
                                    </p>

                                    {announcement.attachment_url && (
                                        <a
                                            href={announcement.attachment_url}
                                            className="text-brand-green-800 inline-flex items-center gap-1 text-sm hover:underline"
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
                                </CardContent>
                            </Card>
                        ))
                    )}

                    <Pagination meta={announcements.meta} />
                </div>
            </div>
        </PublicLayout>
    );
}
