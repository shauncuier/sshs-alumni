import { usePage } from '@inertiajs/react';
import type { PublicSettings } from '@/types/shared';

/**
 * Read a public setting as `group.key`, e.g. `school.eiin`.
 *
 * Only settings explicitly marked public are shared to the frontend, so
 * anything absent here is either unset or deliberately server-only.
 *
 * @see app/Services/Settings/SettingsService.php
 */
export function useSetting<T = unknown>(path: string): T | undefined {
    const page = usePage();
    const settings = (page.props.settings ?? {}) as PublicSettings;

    const separator = path.indexOf('.');

    if (separator === -1) {
        return undefined;
    }

    const group = path.slice(0, separator);
    const key = path.slice(separator + 1);

    return settings[group]?.[key] as T | undefined;
}
