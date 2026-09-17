<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignChannel;
use Carbon\CarbonImmutable;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable notification or campaign template, bilingual per channel.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property CampaignChannel $channel
 * @property string|null $subject
 * @property string $body
 * @property array<array-key, mixed>|null $variables
 * @property bool $is_system
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'key', 'name', 'channel', 'subject', 'body', 'variables', 'is_system',
])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'variables' => 'array',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
