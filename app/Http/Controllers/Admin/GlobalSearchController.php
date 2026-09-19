<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Search across all authorized administrative entities.
     */
    public function __invoke(Request $request, GlobalSearchService $searchService): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $user = $request->user();

        if ($user === null) {
            return response()->json(['results' => []]);
        }

        $results = $searchService->search($query, $user, limit: 6);

        return response()->json([
            'query' => $query,
            'results' => $results,
        ]);
    }
}
