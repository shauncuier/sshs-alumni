<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicMemberResource;
use App\Models\Member;
use App\Support\Qr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The digital membership card.
 *
 * The QR encodes `/verify/member/{ulid}` — a URL the SERVER resolves against
 * the database. Nothing about the member is encoded in the code itself, so
 * photographing someone's card discloses no more than scanning it does, and a
 * forged card can only ever point at a real record or at nothing.
 *
 * The card is rendered with PublicMemberResource, the same six fields the
 * verification page shows. A member looking at their own card therefore sees
 * exactly what anyone scanning it will see — no surprises at the gate.
 *
 * Printing is left to the browser rather than dompdf: Bengali conjunct shaping
 * in dompdf is unreliable, and the member's name may well be the one Bangla
 * string on the card.
 *
 * @see docs/05-modules.md section 16
 */
class CardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $member = $request->user()?->member;

        abort_if($member === null, 404);

        // A card is proof of membership. An application still under review is
        // not one yet, and the middleware has already turned those away — this
        // is the second door.
        abort_unless($member->isApproved(), 403);

        $member->load('batch');

        return Inertia::render('member/card', [
            'member' => PublicMemberResource::make($member),
            'verify_url' => $this->verifyUrl($member),
            'qr' => Qr::dataUri($this->verifyUrl($member), size: 200),
        ]);
    }

    private function verifyUrl(Member $member): string
    {
        return route('verify.member', $member->ulid);
    }
}
