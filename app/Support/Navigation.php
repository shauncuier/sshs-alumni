<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The navigation, built from routes that actually exist.
 *
 * Every item names a ROUTE NAME rather than a URL. An item whose route is not
 * registered yet is simply not rendered, so a half-built phase can never
 * present a link that 404s — and the moment a phase lands, its links appear
 * with no component change.
 *
 * Permission filtering happens here too. That HIDES items; it does not protect
 * anything. Every route is still guarded by `can:` middleware and a policy, so
 * a user who types a hidden URL still gets a 403.
 *
 * @see docs/04-roles-permissions.md section 6
 */
final class Navigation
{
    /**
     * Admin sections, grouped. `permission` gates visibility.
     *
     * @var array<string, array<int, array{key: string, route: string, icon: string, permission: string}>>
     */
    private const ADMIN = [
        'people' => [
            ['key' => 'members', 'route' => 'admin.members.index', 'icon' => 'Users', 'permission' => 'members.view'],
            ['key' => 'crm', 'route' => 'admin.crm.contacts.index', 'icon' => 'Contact', 'permission' => 'crm.view'],
            ['key' => 'pipeline', 'route' => 'admin.crm.pipeline', 'icon' => 'Kanban', 'permission' => 'crm.view'],
            ['key' => 'tasks', 'route' => 'admin.crm.tasks.index', 'icon' => 'ListTodo', 'permission' => 'crm.view'],
            ['key' => 'tags', 'route' => 'admin.crm.tags.index', 'icon' => 'Tags', 'permission' => 'crm.view'],
            ['key' => 'batches', 'route' => 'admin.batches.index', 'icon' => 'GraduationCap', 'permission' => 'batches.view'],
        ],
        'events' => [
            ['key' => 'events', 'route' => 'admin.events.index', 'icon' => 'CalendarDays', 'permission' => 'events.view'],
            // No separate Jubilee entry: it is the flagship EVENT, edited
            // through the events list where it carries a gold mark. A second
            // admin page for it would be the duplication the design avoids.
            ['key' => 'volunteers', 'route' => 'admin.volunteers.index', 'icon' => 'HandHeart', 'permission' => 'volunteers.view'],
            ['key' => 'committees', 'route' => 'admin.committees.index', 'icon' => 'UserSquare', 'permission' => 'committees.view'],
        ],
        'money' => [
            ['key' => 'payments', 'route' => 'admin.payments.index', 'icon' => 'Wallet', 'permission' => 'payments.view'],
            ['key' => 'donations', 'route' => 'admin.donations.index', 'icon' => 'Gift', 'permission' => 'donations.view'],
            ['key' => 'sponsors', 'route' => 'admin.sponsors.index', 'icon' => 'Handshake', 'permission' => 'sponsors.view'],
        ],
        'content' => [
            ['key' => 'news', 'route' => 'admin.news.index', 'icon' => 'Newspaper', 'permission' => 'content.view'],
            ['key' => 'announcements', 'route' => 'admin.announcements.index', 'icon' => 'Megaphone', 'permission' => 'content.view'],
            ['key' => 'gallery', 'route' => 'admin.gallery.index', 'icon' => 'Image', 'permission' => 'content.view'],
            ['key' => 'pages', 'route' => 'admin.pages.index', 'icon' => 'FileText', 'permission' => 'content.view'],
            ['key' => 'history', 'route' => 'admin.history.index', 'icon' => 'ScrollText', 'permission' => 'content.view'],
            ['key' => 'stories', 'route' => 'admin.stories.index', 'icon' => 'BookOpen', 'permission' => 'content.view'],
            ['key' => 'faqs', 'route' => 'admin.faqs.index', 'icon' => 'CircleHelp', 'permission' => 'content.view'],
            ['key' => 'media', 'route' => 'admin.media.index', 'icon' => 'Image', 'permission' => 'content.view'],
            ['key' => 'community', 'route' => 'admin.community.posts', 'icon' => 'MessagesSquare', 'permission' => 'community.moderate'],
        ],
        'communication' => [
            ['key' => 'campaigns', 'route' => 'admin.campaigns.index', 'icon' => 'Send', 'permission' => 'campaigns.manage'],
            ['key' => 'templates', 'route' => 'admin.templates.index', 'icon' => 'FileText', 'permission' => 'campaigns.manage'],
        ],
        'system' => [
            ['key' => 'reports', 'route' => 'admin.reports.index', 'icon' => 'Award', 'permission' => 'reports.view'],
            ['key' => 'users', 'route' => 'admin.users.index', 'icon' => 'Users', 'permission' => 'users.manage'],
            ['key' => 'roles', 'route' => 'admin.roles.index', 'icon' => 'ShieldCheck', 'permission' => 'roles.manage'],
            ['key' => 'audit', 'route' => 'admin.audit.index', 'icon' => 'ScrollText', 'permission' => 'audit.view'],
            ['key' => 'settings', 'route' => 'admin.settings.edit', 'icon' => 'Settings', 'permission' => 'settings.manage'],
        ],
    ];

    /**
     * Member destinations. `approved` items are hidden until the committee
     * verifies the membership — the middleware would redirect anyway, and
     * offering a link that bounces is worse than not offering it.
     *
     * @var array<int, array{key: string, route: string, icon: string, approved?: bool}>
     */
    private const MEMBER = [
        ['key' => 'dashboard', 'route' => 'dashboard', 'icon' => 'LayoutGrid'],
        ['key' => 'notifications', 'route' => 'notifications.index', 'icon' => 'Bell'],
        ['key' => 'profile', 'route' => 'my.profile', 'icon' => 'UserCircle'],
        ['key' => 'card', 'route' => 'my.card', 'icon' => 'IdCard', 'approved' => true],
        ['key' => 'directory', 'route' => 'directory.index', 'icon' => 'Users', 'approved' => true],
        ['key' => 'batch', 'route' => 'my.batch', 'icon' => 'GraduationCap', 'approved' => true],
        ['key' => 'community', 'route' => 'community.index', 'icon' => 'MessagesSquare', 'approved' => true],
        ['key' => 'stories', 'route' => 'my.stories', 'icon' => 'BookOpen', 'approved' => true],
        ['key' => 'events', 'route' => 'my.events', 'icon' => 'CalendarDays'],
        ['key' => 'payments', 'route' => 'my.payments', 'icon' => 'CreditCard'],
        ['key' => 'donations', 'route' => 'my.donations', 'icon' => 'Gift'],
    ];

    /**
     * Public navigation, and the secondary links in the footer.
     *
     * @var array<int, array{key: string, route: string}>
     */
    private const PUBLIC_PRIMARY = [
        ['key' => 'jubilee', 'route' => 'jubilee'],
        ['key' => 'about', 'route' => 'about'],
        ['key' => 'events', 'route' => 'events.index'],
        ['key' => 'batches', 'route' => 'batches.index'],
        ['key' => 'news', 'route' => 'news.index'],
        ['key' => 'gallery', 'route' => 'gallery.index'],
        ['key' => 'contact', 'route' => 'contact'],
    ];

    /**
     * Legal pages. These are CMS pages rather than dedicated routes, so each
     * carries the slug it resolves to.
     *
     * @var array<int, array{key: string, route: string, params: array<string, string>}>
     */
    private const PUBLIC_LEGAL = [
        ['key' => 'privacy', 'route' => 'pages.show', 'params' => ['page' => 'privacy-policy']],
        ['key' => 'terms', 'route' => 'pages.show', 'params' => ['page' => 'terms']],
    ];

    /**
     * @var array<int, array{key: string, route: string}>
     */
    private const PUBLIC_FOOTER = [
        ['key' => 'jubilee', 'route' => 'jubilee'],
        ['key' => 'events', 'route' => 'events.index'],
        ['key' => 'batches', 'route' => 'batches.index'],
        ['key' => 'committees', 'route' => 'committees.index'],
        ['key' => 'stories', 'route' => 'stories.index'],
        ['key' => 'donate', 'route' => 'donate'],
    ];

    /**
     * The navigation this user should see, with every URL resolved.
     *
     * @return array{admin: array<int, array{label: string, items: array<int, array{key: string, href: string, icon: string}>}>, member: array<int, array{key: string, href: string, icon: string}>, publicPrimary: array<int, array{key: string, href: string}>, publicFooter: array<int, array{key: string, href: string}>, publicLegal: array<int, array{key: string, href: string}>}
     */
    public static function for(?User $user, bool $memberApproved = false): array
    {
        return [
            'admin' => self::adminGroups($user),
            'member' => self::memberItems($user, $memberApproved),
            'publicPrimary' => self::publicItems(self::PUBLIC_PRIMARY),
            'publicFooter' => self::publicItems(self::PUBLIC_FOOTER),
            'publicLegal' => self::publicItems(self::PUBLIC_LEGAL),
        ];
    }

    /**
     * @return array<int, array{label: string, items: array<int, array{key: string, href: string, icon: string}>}>
     */
    private static function adminGroups(?User $user): array
    {
        // `admin.access` is the door. Without it the whole panel is a 403, and
        // an ordinary Member holds `batches.view` — enough to light up one
        // sidebar group for someone who can never open the panel.
        if ($user === null || ! $user->can('admin.access')) {
            return [];
        }

        $groups = [];

        foreach (self::ADMIN as $label => $items) {
            $visible = [];

            foreach ($items as $item) {
                if (! self::exists($item['route']) || ! $user->can($item['permission'])) {
                    continue;
                }

                $visible[] = [
                    'key' => $item['key'],
                    'href' => route($item['route'], absolute: false),
                    'icon' => $item['icon'],
                ];
            }

            // An empty group renders nothing rather than an orphan heading.
            if ($visible !== []) {
                $groups[] = ['label' => $label, 'items' => $visible];
            }
        }

        return $groups;
    }

    /**
     * @return array<int, array{key: string, href: string, icon: string}>
     */
    private static function memberItems(?User $user, bool $approved): array
    {
        if ($user === null) {
            return [];
        }

        $items = [];

        foreach (self::MEMBER as $item) {
            if (! self::exists($item['route'])) {
                continue;
            }

            if (($item['approved'] ?? false) && ! $approved) {
                continue;
            }

            $items[] = [
                'key' => $item['key'],
                'href' => route($item['route'], absolute: false),
                'icon' => $item['icon'],
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array{key: string, route: string, params?: array<string, string>}>  $definitions
     * @return array<int, array{key: string, href: string}>
     */
    private static function publicItems(array $definitions): array
    {
        $items = [];

        foreach ($definitions as $item) {
            if (! self::exists($item['route'])) {
                continue;
            }

            $items[] = [
                'key' => $item['key'],
                'href' => route($item['route'], $item['params'] ?? [], absolute: false),
            ];
        }

        return $items;
    }

    private static function exists(string $name): bool
    {
        return Route::has($name);
    }

    /**
     * Every route name the navigation refers to, for the test that asserts
     * none of them is a typo.
     *
     * @return array<int, string>
     */
    public static function allRouteNames(): array
    {
        $names = [];

        foreach (self::ADMIN as $items) {
            foreach ($items as $item) {
                $names[] = $item['route'];
            }
        }

        foreach ([self::MEMBER, self::PUBLIC_PRIMARY, self::PUBLIC_FOOTER, self::PUBLIC_LEGAL] as $group) {
            foreach ($group as $item) {
                $names[] = $item['route'];
            }
        }

        return array_values(array_unique($names));
    }
}
