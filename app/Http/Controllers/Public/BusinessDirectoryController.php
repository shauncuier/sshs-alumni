<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessListingResource;
use App\Models\BusinessListing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BusinessListing::query()
            ->published()
            ->with(['member.batch']);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($city = $request->query('city')) {
            $query->where('city', 'like', '%'.$city.'%');
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('tagline', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('industry', 'like', '%'.$search.'%');
            });
        }

        $businesses = $query->latest('published_at')->paginate(20)->withQueryString();

        return Inertia::render('public/businesses', [
            'businesses' => BusinessListingResource::collection($businesses),
            'filters' => [
                'q' => $request->query('q'),
                'category' => $request->query('category'),
                'city' => $request->query('city'),
            ],
            'categories' => BusinessListing::query()->published()->distinct()->pluck('category')->filter()->values(),
        ]);
    }

    public function show(BusinessListing $business): Response
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['member.batch']);

        return Inertia::render('public/business-show', [
            'business' => BusinessListingResource::make($business),
        ]);
    }
}
