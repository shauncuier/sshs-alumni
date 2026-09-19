<?php

declare(strict_types=1);

namespace App\Services\Search\Contracts;

use App\Models\User;

interface SearchDriver
{
    /**
     * Search across multiple entity domains.
     *
     * @return array<string, array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>>
     */
    public function search(string $query, User $user, int $limit = 5): array;
}
