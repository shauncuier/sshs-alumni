import { Inbox } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';

type Props = {
    title: string;
    /** Say what to do next, not just that there is nothing. */
    description?: string;
    icon?: ComponentType<{ className?: string }>;
    action?: ReactNode;
};

/**
 * The empty state every list uses.
 *
 * Specific text beats "No data" — a member looking at an empty batch page
 * should learn why it is empty and what would change that.
 *
 * @see docs/07-branding-ui.md section 8
 */
export function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
}: Props) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-14 text-center">
            <Icon className="text-muted-foreground size-9" aria-hidden="true" />

            <h2 className="mt-4 font-medium">{title}</h2>

            {description && (
                <p className="text-muted-foreground mt-1 max-w-prose text-sm">
                    {description}
                </p>
            )}

            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
