<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicMemberResource;
use App\Models\Member;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The QR target from a digital membership card.
 *
 * Reads the member from the DATABASE by the ULID in the URL — never from the
 * QR payload itself. A forged card can therefore only ever point at a real
 * record or at nothing.
 *
 * Serialised by PublicMemberResource, which exposes six fields and does not
 * widen for any privacy setting. Rate limited, and never indexed.
 *
 * @see docs/05-modules.md section 16
 */
class MemberVerifyController extends Controller
{
    public function __invoke(string $ulid): Response
    {
        $member = Member::query()
            ->where('ulid', $ulid)
            ->with('batch')
            ->first();

        return Inertia::render('public/verify-member', [
            // A missing or unknown ULID renders "not found" rather than a 404,
            // so someone holding a card gets an answer instead of an error
            // page they have to interpret.
            'member' => $member === null
                ? null
                : PublicMemberResource::make($member),
        ]);
    }
}
