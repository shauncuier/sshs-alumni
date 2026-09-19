import { Link, router } from '@inertiajs/react';
import {
    AtSign,
    Bell,
    Check,
    CheckCheck,
    CheckCircle2,
    Clock,
    Megaphone,
    Receipt,
} from 'lucide-react';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatDateTime } from '@/lib/format';
import type { NotificationItem } from '@/types/campaign';
import type { Paginated } from '@/types/member';

type Props = {
    notifications: Paginated<NotificationItem>;
    unreadCount: number;
    filter: string;
};

export default function MemberNotifications({
    notifications,
    unreadCount,
    filter,
}: Props) {
    const handleMarkAllRead = () => {
        router.post('/notifications/read-all');
    };

    const handleMarkRead = (id: string) => {
        router.post(`/notifications/${id}/read`);
    };

    const renderIcon = (category?: string, customIcon?: string | null) => {
        switch (category) {
            case 'community':
                return <AtSign className="h-5 w-5 text-teal-400" />;
            case 'membership':
                return <CheckCircle2 className="h-5 w-5 text-emerald-400" />;
            case 'payment':
                return <Receipt className="h-5 w-5 text-amber-400" />;
            case 'announcement':
                return <Megaphone className="h-5 w-5 text-indigo-400" />;
            default:
                return <Bell className="h-5 w-5 text-teal-400" />;
        }
    };

    return (
        <MemberLayout title="Notification Center">
            <div className="mx-auto max-w-4xl space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                                Notifications
                            </h1>
                            {unreadCount > 0 && (
                                <Badge className="bg-teal-500/20 text-teal-300 border-teal-500/30">
                                    {unreadCount} Unread
                                </Badge>
                            )}
                        </div>
                        <p className="mt-1 text-sm text-slate-400">
                            Updates on your membership status, event announcements, and community discussions.
                        </p>
                    </div>

                    {unreadCount > 0 && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleMarkAllRead}
                            className="border-teal-500/20 bg-slate-900/60 text-teal-300 hover:bg-slate-800"
                        >
                            <CheckCheck className="mr-2 h-4 w-4" />
                            Mark All Read
                        </Button>
                    )}
                </div>

                {/* Filter Tabs */}
                <div className="flex items-center gap-2 border-b border-teal-500/10 pb-3">
                    <Link href="/notifications?filter=all">
                        <Button
                            variant={filter === 'all' ? 'default' : 'ghost'}
                            size="sm"
                            className={
                                filter === 'all'
                                    ? 'bg-teal-500 text-slate-950 font-semibold'
                                    : 'text-slate-400 hover:text-slate-200'
                            }
                        >
                            All ({notifications.meta.total})
                        </Button>
                    </Link>
                    <Link href="/notifications?filter=unread">
                        <Button
                            variant={filter === 'unread' ? 'default' : 'ghost'}
                            size="sm"
                            className={
                                filter === 'unread'
                                    ? 'bg-teal-500 text-slate-950 font-semibold'
                                    : 'text-slate-400 hover:text-slate-200'
                            }
                        >
                            Unread ({unreadCount})
                        </Button>
                    </Link>
                </div>

                {/* Notification List */}
                {notifications.data.length === 0 ? (
                    <EmptyState
                        icon={Bell}
                        title="You're all caught up"
                        description={
                            filter === 'unread'
                                ? 'No unread notifications right now.'
                                : 'When announcements, mentions, or approvals arrive, they will appear here.'
                        }
                    />
                ) : (
                    <div className="space-y-3">
                        {notifications.data.map((item) => {
                            const isUnread = !item.read_at;

                            return (
                                <Card
                                    key={item.id}
                                    className={`border transition-all ${
                                        isUnread
                                            ? 'border-teal-500/30 bg-slate-900/80 shadow-md shadow-teal-500/5'
                                            : 'border-slate-800/80 bg-slate-950/40'
                                    }`}
                                >
                                    <CardContent className="p-4 sm:p-5">
                                        <div className="flex items-start gap-4">
                                            <div
                                                className={`rounded-full p-2.5 ${
                                                    isUnread
                                                        ? 'bg-teal-500/15 text-teal-300 ring-1 ring-teal-500/30'
                                                        : 'bg-slate-900 text-slate-500'
                                                }`}
                                            >
                                                {renderIcon(item.data.category, item.data.icon)}
                                            </div>

                                            <div className="flex-1 space-y-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <h3
                                                        className={`text-sm font-semibold ${
                                                            isUnread ? 'text-slate-100' : 'text-slate-300'
                                                        }`}
                                                    >
                                                        {item.data.title}
                                                    </h3>
                                                    <span className="flex items-center gap-1 text-[11px] text-slate-500">
                                                        <Clock className="h-3 w-3" />
                                                        {formatDateTime(item.created_at)}
                                                    </span>
                                                </div>

                                                <p className="text-xs text-slate-400 whitespace-pre-line leading-relaxed">
                                                    {item.data.body}
                                                </p>

                                                <div className="mt-3 flex items-center justify-between pt-2">
                                                    {item.data.action_url ? (
                                                        <a
                                                            href={item.data.action_url}
                                                            className="text-xs font-semibold text-teal-400 hover:text-teal-300 hover:underline"
                                                        >
                                                            {item.data.action_text || 'View details'} &rarr;
                                                        </a>
                                                    ) : (
                                                        <span />
                                                    )}

                                                    {isUnread && (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => handleMarkRead(item.id)}
                                                            className="h-7 text-xs text-slate-400 hover:text-teal-300"
                                                        >
                                                            <Check className="mr-1 h-3 w-3" />
                                                            Mark read
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Pagination */}
                <Pagination meta={notifications.meta} />
            </div>
        </MemberLayout>
    );
}
