<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MemberLink
 */
class MemberLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->type->label(),
            'url' => $this->url,
        ];
    }
}
