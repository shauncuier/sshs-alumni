import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { FlashMessages } from '@/types/shared';

/**
 * Surfaces server flash messages as toasts.
 *
 * Keyed on the message itself so the same notice is not re-toasted on every
 * partial reload — only when it actually changes.
 */
export function useFlashMessages(): void {
    const page = usePage();
    const flash = (page.props.flash ?? {}) as FlashMessages;
    const lastShown = useRef<string | null>(null);

    useEffect(() => {
        const entries: [keyof FlashMessages, (message: string) => void][] = [
            ['success', toast.success],
            ['error', toast.error],
            ['warning', toast.warning],
            ['info', toast.info],
        ];

        for (const [key, show] of entries) {
            const message = flash[key];

            if (!message) {
                continue;
            }

            const signature = `${key}:${message}`;

            if (lastShown.current === signature) {
                continue;
            }

            lastShown.current = signature;
            show(message);

            return;
        }
    }, [flash]);
}
