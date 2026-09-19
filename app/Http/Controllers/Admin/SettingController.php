<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SettingGroup;
use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Request $request, SettingsService $settingsService): Response
    {
        $currentGroup = $request->query('group', SettingGroup::Organization->value);
        if (! in_array($currentGroup, array_column(SettingGroup::cases(), 'value'), true)) {
            $currentGroup = SettingGroup::Organization->value;
        }

        $allSettings = $settingsService->all();
        $groupSettings = $allSettings[$currentGroup] ?? [];

        $groups = array_map(fn (SettingGroup $g): array => [
            'value' => $g->value,
            'label' => $g->label(),
        ], SettingGroup::cases());

        return Inertia::render('admin/settings/edit', [
            'currentGroup' => $currentGroup,
            'groupSettings' => $groupSettings,
            'allSettings' => $allSettings,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, string $group, SettingsService $settingsService): RedirectResponse
    {
        $validGroup = SettingGroup::tryFrom($group);
        if ($validGroup === null) {
            abort(404, "Unknown settings group [{$group}].");
        }

        $values = $request->input('settings', []);
        if (! is_array($values)) {
            $values = [];
        }

        // Determine if setting key should be marked public
        $publicKeys = [
            'organization.name',
            'organization.name_bn',
            'organization.established_year',
            'organization.logo_url',
            'school.name',
            'school.name_bn',
            'school.established_year',
            'school.eiin',
            'contact.email',
            'contact.phone',
            'contact.address',
            'social.facebook',
            'social.youtube',
            'jubilee.theme',
            'jubilee.countdown_enabled',
            'privacy.public_directory',
            'system.default_locale',
        ];

        foreach ($values as $key => $value) {
            $fullPath = "{$group}.{$key}";
            $isPublic = in_array($fullPath, $publicKeys, true);
            $settingsService->set($fullPath, $value, $isPublic);
        }

        return back()->with('success', "Settings for {$validGroup->label()} updated successfully.");
    }
}
