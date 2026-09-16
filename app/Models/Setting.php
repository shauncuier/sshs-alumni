<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingGroup;
use Carbon\CarbonImmutable;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A grouped key/value setting, read through SettingsService and cached.
 *
 * `organization` (association, est. 2015) and `school` (est. 1976) are separate
 * groups on purpose — the two bodies must never be conflated.
 *
 * Only `is_public` settings are shared to the frontend.
 *
 * @property int $id
 * @property SettingGroup $group
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property string $type
 * @property bool $is_public
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'group', 'key', 'value', 'type', 'is_public',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => SettingGroup::class,
            'value' => 'json',
            'is_public' => 'boolean',
        ];
    }
}
