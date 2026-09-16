<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Shared behaviour for every backed enum in the application.
 *
 * Enums never render their raw value. Labels resolve through the active
 * locale so the same case reads correctly in বাংলা and English.
 *
 * @see docs/06-localization.md
 */
trait HasLabel
{
    /**
     * The human label for this case in the active locale.
     */
    public function label(): string
    {
        /** @var string $label */
        $label = __('enums.'.static::translationKey().'.'.$this->value);

        return $label;
    }

    /**
     * The `lang/{locale}/enums.php` section holding this enum's labels.
     */
    public static function translationKey(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /**
     * Every backing value, for validation rules and migrations.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Every case as a select option, labelled for the active locale.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            static::cases(),
        );
    }
}
