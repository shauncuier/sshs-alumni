import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

/**
 * Permission checks for HIDING UI.
 *
 * This is a courtesy, not a control. Every protected route is enforced
 * server-side by a policy or `can:` middleware, and a user who forges their
 * way past a hidden button still gets a 403. Never rely on this for anything
 * that matters.
 *
 * @see docs/04-roles-permissions.md section 6
 */
export function usePermission() {
    const page = usePage();
    const auth = page.props.auth as
        | { permissions?: string[]; roles?: string[] }
        | undefined;

    const permissions = auth?.permissions ?? [];
    const roles = auth?.roles ?? [];

    const can = useCallback(
        (permission: string): boolean =>
            roles.includes('Super Admin') || permissions.includes(permission),
        [permissions, roles],
    );

    const canAny = useCallback(
        (...wanted: string[]): boolean => wanted.some((one) => can(one)),
        [can],
    );

    const hasRole = useCallback(
        (role: string): boolean => roles.includes(role),
        [roles],
    );

    return { can, canAny, hasRole, permissions, roles };
}
