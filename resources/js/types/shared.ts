/**
 * Props shared on every Inertia response by HandleInertiaRequests.
 *
 * @see app/Http/Middleware/HandleInertiaRequests.php
 */

export type Locale = 'bn' | 'en';

/** Flattened `file.key.subkey` map for the ACTIVE locale only. */
export type Translations = Record<string, string>;

/**
 * Settings explicitly marked public. Anything not marked public never leaves
 * the server, so this is not the whole settings table.
 */
export type PublicSettings = Record<string, Record<string, unknown>>;

export type FlashMessages = {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
};

export type SharedProps = {
    locale: Locale;
    locales: Record<Locale, string>;
    translations: Translations;
    settings: PublicSettings;
    flash: FlashMessages;
};

/** One navigation destination, resolved server-side to a real URL. */
export type NavItem = {
    key: string;
    href: string;
    icon: string;
};

export type AdminNavGroup = {
    label: string;
    items: NavItem[];
};

export type PublicNavItem = {
    key: string;
    href: string;
};

/**
 * Navigation built from routes that actually exist, so the UI cannot render a
 * link to a phase that has not shipped.
 *
 * @see app/Support/Navigation.php
 */
export type SharedNav = {
    admin: AdminNavGroup[];
    member: NavItem[];
    publicPrimary: PublicNavItem[];
    publicFooter: PublicNavItem[];
    publicLegal: PublicNavItem[];
};
