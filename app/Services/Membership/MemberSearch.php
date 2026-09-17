<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shared filtering for the directory and the admin member list.
 *
 * One implementation so the two surfaces cannot drift — a filter that works in
 * the admin panel behaves identically in the directory, and neither can
 * accidentally widen what the other shows.
 *
 * @see docs/05-modules.md section 3
 */
class MemberSearch
{
    /**
     * Filters accepted from the query string.
     *
     * @var array<int, string>
     */
    public const FILTERS = [
        'q', 'batch_id', 'ssc_year', 'district', 'city', 'country',
        'occupation', 'industry', 'blood_group', 'relation_type', 'status',
    ];

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function apply(Builder $query, Request $request): Builder
    {
        $term = trim((string) $request->query('q', ''));

        if ($term !== '') {
            // One indexed LIKE against the denormalised blob rather than five
            // OR-ed column scans. See MemberObserver.
            $needle = '%'.Str::lower($term).'%';

            $query->where(function (Builder $inner) use ($needle, $term): void {
                $inner->where('search_blob', 'like', $needle)
                    // Membership numbers stay in Latin digits and are not part
                    // of the lowercase blob in a useful way, so match directly.
                    ->orWhere('membership_no', 'like', '%'.$term.'%');
            });
        }

        foreach (['batch_id', 'ssc_year', 'blood_group', 'relation_type', 'status'] as $field) {
            $value = $request->query($field);

            if (filled($value)) {
                $query->where($field, $value);
            }
        }

        foreach (['district', 'city', 'country', 'occupation', 'industry'] as $field) {
            $value = $request->query($field);

            if (filled($value)) {
                $query->where($field, 'like', '%'.$value.'%');
            }
        }

        return $query;
    }

    /**
     * The active filters, for echoing back to the UI.
     *
     * @return array<string, string|null>
     */
    public function active(Request $request): array
    {
        $active = [];

        foreach (self::FILTERS as $filter) {
            $value = $request->query($filter);
            $active[$filter] = filled($value) ? (string) $value : null;
        }

        return $active;
    }
}
