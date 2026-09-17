<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Batch;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A batch as the admin panel sees it.
 *
 * This exists rather than an inline array so the paginated list arrives in the
 * `{data, meta, links}` shape the Pagination component reads — a bare
 * paginator serialises its page numbers at the top level instead, and the
 * component then renders nothing.
 *
 * @mixin Batch
 */
class AdminBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'ssc_year' => $this->ssc_year,
            // The counter cache, not a COUNT(*). It includes members who have
            // hidden themselves from the batch roll.
            'members_count' => $this->members_count,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'coordinators' => $this->whenLoaded(
                'coordinators',
                fn (): array => $this->coordinators
                    ->map(fn (Member $member): array => [
                        // Members are bound by ULID, so that is what the
                        // remove link has to carry.
                        'ulid' => $member->ulid,
                        'name' => $member->full_name,
                        'membership_no' => $member->membership_no,
                        'photo_url' => $member->photo_path === null
                            ? null
                            : asset('storage/'.$member->photo_path),
                    ])
                    ->all(),
                [],
            ),
        ];
    }
}
