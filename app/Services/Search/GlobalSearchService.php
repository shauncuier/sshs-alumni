<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\User;
use App\Services\Search\Contracts\SearchDriver;

class GlobalSearchService
{
    public function __construct(
        protected SearchDriver $driver,
    ) {}

    /**
     * Perform global search across all authorized domains.
     *
     * @return array<string, array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>>
     */
    public function search(string $query, User $user, int $limit = 5): array
    {
        return $this->driver->search($query, $user, $limit);
    }
}
