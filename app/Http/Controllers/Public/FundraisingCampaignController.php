<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundraisingCampaignResource;
use App\Models\FundraisingCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FundraisingCampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $campaigns = FundraisingCampaign::query()
            ->published()
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(9);

        return Inertia::render('public/fundraising', [
            'campaigns' => FundraisingCampaignResource::collection($campaigns),
        ]);
    }

    public function show(FundraisingCampaign $campaign): Response
    {
        abort_unless($campaign->isPublished(), 404);

        $campaign->load(['donations' => fn ($q) => $q->where('is_public', true)->latest()->take(10)]);

        return Inertia::render('public/fundraising-show', [
            'campaign' => FundraisingCampaignResource::make($campaign),
        ]);
    }
}
