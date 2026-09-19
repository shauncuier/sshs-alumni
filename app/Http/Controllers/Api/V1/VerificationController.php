<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicMemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

/**
 * Public member verification API for digital membership cards.
 *
 * @see docs/10-api.md section 3
 * @see docs/08-security-privacy.md section 2
 */
class VerificationController extends Controller
{
    public function member(string $ulid): JsonResponse
    {
        $member = Member::query()
            ->where('ulid', $ulid)
            ->with('batch')
            ->first();

        if ($member === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Member not found.',
                'member' => null,
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'member' => PublicMemberResource::make($member)->resolve(),
        ]);
    }
}
