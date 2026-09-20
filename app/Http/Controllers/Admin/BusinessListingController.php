<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\BusinessListing;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessListingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
        ];

        $listings = BusinessListing::query()
            ->with(['member:id,ulid,full_name,membership_no'])
            ->when($filters['q'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category', $filters['category']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/businesses/index', [
            'businesses' => Paginated::from($listings, fn (BusinessListing $b): array => [
                'id' => $b->id,
                'ulid' => $b->ulid,
                'name' => $b->name,
                'category' => $b->category,
                'city' => $b->city,
                'phone' => $b->phone,
                'email' => $b->email,
                'alumni_discount' => $b->alumni_discount,
                'status' => $b->status->value,
                'member' => $b->member ? [
                    'ulid' => $b->member->ulid,
                    'full_name' => $b->member->full_name,
                    'membership_no' => $b->member->membership_no,
                ] : null,
                'created_at' => $b->created_at?->toIso8601String(),
            ]),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'statuses' => ContentStatus::cases(),
        ]);
    }

    public function togglePublish(BusinessListing $business): RedirectResponse
    {
        if ($business->status === ContentStatus::Published) {
            $business->update([
                'status' => ContentStatus::Draft,
                'published_at' => null,
            ]);
            $msg = 'Business listing unpublished.';
        } else {
            $business->update([
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ]);
            $msg = 'Business listing published.';
        }

        return back()->with('success', $msg);
    }

    public function destroy(BusinessListing $business): RedirectResponse
    {
        $business->delete();

        return back()->with('success', 'Business listing removed.');
    }
}
