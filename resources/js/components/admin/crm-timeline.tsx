import {
    Calendar,
    Mail,
    MessageSquare,
    Phone,
    Settings2,
    StickyNote,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format';
import type { Activity } from '@/types/crm';

type Props = {
    activities: Activity[];
    /**
     * Whether to mark which of the person's two records an entry came from.
     * Only worth showing once the records are linked.
     */
    showSource?: boolean;
};

const ICONS: Record<string, ComponentType<{ className?: string }>> = {
    note: StickyNote,
    call: Phone,
    email: Mail,
    meeting: Calendar,
    task: MessageSquare,
    system: Settings2,
};

/**
 * One person's history, in order.
 *
 * `system` entries are written by services when something actually happened —
 * a verification, a registration. They are visually quieter than a call
 * somebody made, because a feed where the automatic entries shout drowns the
 * human ones.
 *
 * @see app/Services/Crm/ActivityLogger.php
 */
export function CrmTimeline({ activities, showSource = false }: Props) {
    const { t } = useTranslation();

    if (activities.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                {t('admin.crm.timeline_empty')}
            </p>
        );
    }

    return (
        <ol className="space-y-4">
            {activities.map((activity) => {
                const Icon = ICONS[activity.type] ?? StickyNote;
                const isSystem = activity.type === 'system';

                return (
                    <li key={activity.id} className="flex gap-3">
                        <span
                            aria-hidden="true"
                            className={
                                isSystem
                                    ? 'bg-muted text-muted-foreground mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full'
                                    : 'bg-brand-green-100 text-brand-green-800 mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full'
                            }
                        >
                            <Icon className="size-4" />
                        </span>

                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <span
                                    className={
                                        isSystem
                                            ? 'text-muted-foreground text-sm'
                                            : 'text-sm font-medium'
                                    }
                                >
                                    {activity.subject_line ??
                                        activity.type_label}
                                </span>

                                {!isSystem && (
                                    <Badge
                                        variant="outline"
                                        className="text-xs"
                                    >
                                        {activity.type_label}
                                    </Badge>
                                )}

                                {showSource && activity.on === 'member' && (
                                    <Badge
                                        variant="secondary"
                                        className="text-xs"
                                    >
                                        {t('admin.crm.timeline_on_member')}
                                    </Badge>
                                )}
                            </div>

                            {activity.body && (
                                <p className="text-muted-foreground mt-1 text-sm leading-relaxed whitespace-pre-line">
                                    {activity.body}
                                </p>
                            )}

                            <p className="text-muted-foreground mt-1 text-xs">
                                {formatDateTime(activity.occurred_at)}
                                {activity.user ? ` · ${activity.user}` : ''}
                                {activity.outcome
                                    ? ` · ${activity.outcome}`
                                    : ''}
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
