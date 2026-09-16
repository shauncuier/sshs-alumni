<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\FaqGroup;
use Carbon\CarbonImmutable;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A frequently asked question, grouped by topic.
 *
 * @property int $id
 * @property FaqGroup $group
 * @property string $question
 * @property string|null $question_bn
 * @property string $answer
 * @property string|null $answer_bn
 * @property int $display_order
 * @property bool $is_published
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'group', 'question', 'question_bn', 'answer', 'answer_bn', 'display_order', 'is_published',
])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['question', 'answer'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => FaqGroup::class,
            'is_published' => 'boolean',
        ];
    }
}
