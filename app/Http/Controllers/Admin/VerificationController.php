<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignMembershipNumberRequest;
use App\Http\Requests\Admin\MemberTransitionRequest;
use App\Http\Requests\Admin\RequestCorrectionRequest;
use App\Models\Member;
use App\Services\Membership\VerificationService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Moves an application through the verification workflow.
 *
 * Thin by design: the state machine, the history and the membership number all
 * live in VerificationService, so every path through them behaves the same.
 *
 * @see docs/05-modules.md section 2
 */
class VerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
    ) {}

    public function transition(MemberTransitionRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('verify', $member);

        $to = MemberStatus::from((string) $request->validated('status'));

        try {
            $this->verification->transition(
                $member,
                $to,
                $request->user(),
                $request->validated('note'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('admin.verification.transitioned', [
            'status' => $to->label(),
        ]));
    }

    public function requestCorrection(
        RequestCorrectionRequest $request,
        Member $member,
    ): RedirectResponse {
        $this->authorize('verify', $member);

        $this->verification->requestCorrection(
            $member,
            $request->user(),
            (string) $request->validated('message'),
            $request->validated('note'),
        );

        return back()->with('success', __('admin.verification.correction_sent'));
    }

    public function assignNumber(
        AssignMembershipNumberRequest $request,
        Member $member,
    ): RedirectResponse {
        $this->authorize('verify', $member);

        $member = $this->verification->assignNumber(
            $member,
            $request->user(),
            $request->validated('membership_no'),
        );

        return back()->with('success', __('admin.verification.number_assigned', [
            'number' => $member->membership_no,
        ]));
    }
}
