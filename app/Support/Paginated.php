<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reshapes a paginator into the `{data, meta}` envelope the frontend expects.
 *
 * WHY THIS EXISTS. A bare paginator serialises its page numbers at the TOP
 * level — `{data, current_page, last_page, links, …}`. An API Resource
 * collection nests them under `meta`. The Pagination component reads `meta`,
 * so a controller that returns `$query->paginate()->through(...)` renders a
 * page that throws on `meta.last_page` and shows nothing.
 *
 * That bug has now been shipped twice, in two different phases, because the
 * broken shape looks identical in a `->has('rows.data', 3)` assertion. So:
 * every controller that maps rows inline goes through here, every controller
 * that has a Resource uses the Resource, and a test walks the paginated pages
 * to prove `meta.links` is present.
 *
 * @see tests/Feature/Foundation/PaginationShapeTest.php
 */
final class Paginated
{
    /**
     * @template TItem
     *
     * @param  LengthAwarePaginator<int, TItem>  $paginator
     * @param  Closure(TItem): array<string, mixed>  $map
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public static function from(LengthAwarePaginator $paginator, Closure $map): array
    {
        return [
            'data' => array_values(array_map($map, $paginator->items())),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                // The numbered page links the component renders. Laravel puts
                // these inside `meta` for a Resource collection, so this
                // matches rather than inventing a second shape.
                'links' => $paginator->linkCollection()->toArray(),
            ],
        ];
    }
}
