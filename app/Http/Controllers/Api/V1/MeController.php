<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DonationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RegistrationResource;
use App\Models\Donation;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Payment;
use App\Support\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated member's self-service endpoints.
 *
 * @see docs/10-api.md section 3
 */
class MeController extends Controller
{
    /**
     * The member's full profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $member->load(['batch', 'privacy', 'links']);

        return response()->json([
            'profile' => [
                'ulid' => $member->ulid,
                'full_name' => $member->full_name,
                'relation_type' => $member->relation_type->value,
                'relation_label' => $member->relation_type->label(),
                'membership_no' => $member->membership_no,
                'status' => $member->status->value,
                'ssc_year' => $member->ssc_year,
                'batch' => $member->batch?->name,
                'email' => $member->email,
                'mobile' => $member->mobile,
                'whatsapp' => $member->whatsapp,
                'bio' => $member->bio,
                'occupation' => $member->occupation,
                'organization' => $member->organization,
                'job_title' => $member->job_title,
                'industry' => $member->industry,
                'city' => $member->city,
                'district' => $member->district,
                'division' => $member->division,
                'country' => $member->country,
                'photo_url' => $member->photo_path ? asset('storage/'.$member->photo_path) : null,
                'privacy' => $member->privacy,
            ],
        ]);
    }

    /**
     * Update the member's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $member->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'member' => DirectoryMemberResource::make($member->fresh(['batch', 'privacy']))->resolve(),
        ]);
    }

    /**
     * Digital membership card payload.
     */
    public function card(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        if (! $member->isApproved()) {
            return response()->json([
                'message' => 'Your membership is pending approval. A digital card is available once approved.',
            ], 403);
        }

        return response()->json([
            'card' => [
                'ulid' => $member->ulid,
                'membership_no' => $member->membership_no,
                'full_name' => $member->full_name,
                'ssc_year' => $member->ssc_year,
                'batch' => $member->batch?->name,
                'photo_url' => $member->photo_path ? asset('storage/'.$member->photo_path) : null,
                'verified_at' => $member->verified_at?->toIso8601String(),
                'status' => $member->status->value,
                'verification_url' => url('/verify/member/'.$member->ulid),
            ],
        ]);
    }

    /**
     * Events the member has registered for and ticket passes.
     */
    public function events(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $registrations = EventRegistration::query()
            ->where('member_id', $member->id)
            ->with(['event', 'ticketType', 'checkin.operator'])
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'registrations' => RegistrationResource::collection($registrations)->response()->getData(true),
        ]);
    }

    /**
     * The member's notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * The member's payment history.
     */
    public function payments(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $payments = Payment::query()
            ->where('payer_member_id', $member->id)
            ->with(['payable', 'recorder'])
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'payments' => PaymentResource::collection($payments)->response()->getData(true),
            'totals' => [
                'paid' => (float) Payment::query()
                    ->where('payer_member_id', $member->id)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount'),
                'currency' => (string) config('payments.currency', 'BDT'),
            ],
        ]);
    }

    /**
     * The member's donation history.
     */
    public function donations(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $donations = Donation::query()
            ->where('donor_member_id', $member->id)
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'donations' => Paginated::from($donations, fn (Donation $donation): array => [
                'ulid' => $donation->ulid,
                'amount' => (float) $donation->amount,
                'currency' => $donation->currency,
                'campaign' => $donation->campaign,
                'status' => $donation->status->value,
                'status_label' => $donation->status->label(),
                'is_anonymous' => $donation->is_anonymous,
                'received_at' => $donation->received_at?->toDateString(),
            ]),
            'total' => (float) Donation::query()
                ->where('donor_member_id', $member->id)
                ->where('status', DonationStatus::Received)
                ->sum('amount'),
            'currency' => (string) config('payments.currency', 'BDT'),
        ]);
    }

    private function resolveMember(Request $request): Member
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $member = $user->member;
        abort_if($member === null, 404, 'No member record linked to this account.');

        return $member;
    }
}
