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
