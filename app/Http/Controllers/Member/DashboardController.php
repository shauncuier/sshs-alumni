<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Membership\ProfileCompletionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ProfileCompletionCalculator $completion,
    ): Response|RedirectResponse {
        $user = $request->user();

        // Staff land in the admin panel rather than a member dashboard they
        // have no record behind.
        if ($user?->can('admin.access') && $user->member === null) {
            return redirect()->route('admin.dashboard');
        }

        $member = $user?->member;

        return Inertia::render('member/dashboard', [
            'member' => $member === null ? null : [
                'ulid' => $member->ulid,
                'full_name' => $member->full_name,
                'membership_no' => $member->membership_no,
                'status' => $member->status->value,
                'status_label' => $member->status->label(),
                'is_approved' => $member->isApproved(),
                'profile_completion' => $member->profile_completion,
                'missing_groups' => $completion->missingGroups($member),
                'batch' => $member->batch?->name,
            ],
        ]);
    }
}
