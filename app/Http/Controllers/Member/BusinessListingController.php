<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\BusinessListingRequest;
use App\Http\Resources\BusinessListingResource;
use App\Models\BusinessListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessListingController extends Controller
{
    public function index(Request $request): Response
    {
        $member = $request->user()?->member;
        abort_unless($member !== null, 403);

        $listings = $member->businessListings()
            ->latest()
            ->paginate(15);

        return Inertia::render('member/businesses', [
            'businesses' => BusinessListingResource::collection($listings),
        ]);
    }

    public function store(BusinessListingRequest $request): RedirectResponse
    {
        $member = $request->user()?->member;
        abort_unless($member !== null, 403);

        $member->businessListings()->create($request->validated());

        return back()->with('success', 'Business listing created successfully.');
    }

    public function update(BusinessListingRequest $request, BusinessListing $business): RedirectResponse
    {
        $this->authorize('update', $business);

        $business->update($request->validated());

        return back()->with('success', 'Business listing updated successfully.');
    }

    public function destroy(Request $request, BusinessListing $business): RedirectResponse
    {
        $this->authorize('delete', $business);

        $business->delete();

        return back()->with('success', 'Business listing removed successfully.');
    }
}
