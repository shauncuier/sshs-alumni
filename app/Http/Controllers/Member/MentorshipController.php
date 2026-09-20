<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MentorProfileRequest;
use App\Http\Requests\Member\MentorshipInquiryRequest;
use App\Http\Resources\MentorProfileResource;
use App\Http\Resources\MentorshipRequestResource;
use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MentorshipController extends Controller
{
    public function index(Request $request): Response
    {
        $member = $request->user()?->member;
        abort_unless($member !== null && $member->isApproved(), 403);

        $query = MentorProfile::query()
            ->available()
            ->with(['member.batch'])
            ->where('member_id', '!=', $member->id);

        if ($topic = $request->query('topic')) {
            $query->whereJsonContains('expertise', $topic);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('company_or_institution', 'like', '%'.$search.'%')
                    ->orWhere('bio', 'like', '%'.$search.'%');
            });
        }

        $mentors = $query->latest()->paginate(12)->withQueryString();

        $myProfile = MentorProfile::query()
            ->where('member_id', $member->id)
            ->first();

        $incomingRequests = MentorshipRequest::query()
            ->where('mentor_id', $member->id)
            ->with(['mentee.batch'])
            ->latest()
            ->get();

        $outgoingRequests = MentorshipRequest::query()
            ->where('mentee_id', $member->id)
            ->with(['mentor.batch'])
            ->latest()
            ->get();

        return Inertia::render('member/mentorship', [
            'mentors' => MentorProfileResource::collection($mentors),
            'my_profile' => $myProfile ? MentorProfileResource::make($myProfile) : null,
            'incoming_requests' => MentorshipRequestResource::collection($incomingRequests),
            'outgoing_requests' => MentorshipRequestResource::collection($outgoingRequests),
            'filters' => [
                'q' => $request->query('q'),
                'topic' => $request->query('topic'),
            ],
        ]);
    }

    public function storeProfile(MentorProfileRequest $request): RedirectResponse
    {
        $member = $request->user()?->member;
        abort_unless($member !== null && $member->isApproved(), 403);

        MentorProfile::updateOrCreate(
            ['member_id' => $member->id],
            $request->validated(),
        );

        return back()->with('success', 'Mentor profile updated successfully.');
    }

    public function requestMentorship(MentorshipInquiryRequest $request): RedirectResponse
    {
        $member = $request->user()?->member;
        abort_unless($member !== null && $member->isApproved(), 403);

        abort_if(
            (int) $request->input('mentor_id') === $member->id,
            422,
            'You cannot request mentorship from yourself.',
        );

        MentorshipRequest::create([
            'mentor_id' => $request->input('mentor_id'),
            'mentee_id' => $member->id,
            'topic' => $request->input('topic'),
            'message' => $request->input('message'),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Mentorship request sent successfully.');
    }

    public function respond(Request $request, MentorshipRequest $mentorshipRequest): RedirectResponse
    {
        $member = $request->user()?->member;
        abort_unless($member !== null && $mentorshipRequest->mentor_id === $member->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:accepted,rejected,completed'],
            'response_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $mentorshipRequest->update([
            'status' => $validated['status'],
            'response_note' => $validated['response_note'] ?? null,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Mentorship request updated.');
    }
}
