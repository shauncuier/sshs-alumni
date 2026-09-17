<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SchoolMilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A milestone on the school-history timeline.
 *
 * Seeded with 1976 (school founded) and 2015 (association founded). The fifty
 * years belong to the school; the timeline shows where the association joins
 * that story.
 *
 * @property int $id
 * @property int $year
 * @property string|null $date_label
 * @property string $title
 * @property string|null $description
 * @property string|null $image_path
 * @property int $display_order
 * @property bool $is_highlighted
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'year', 'date_label', 'title', 'description', 'image_path',
    'display_order', 'is_highlighted',
])]
class SchoolMilestone extends Model
{
    /** @use HasFactory<SchoolMilestoneFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_highlighted' => 'boolean',
        ];
    }
}
