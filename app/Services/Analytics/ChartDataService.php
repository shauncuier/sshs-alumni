<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\EventStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Models\Batch;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class ChartDataService
{
    /**
     * Build all 7 core analytical charts for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardCharts(): array
    {
        return [
            'members_over_time' => $this->membersOverTime(),
            'members_by_batch' => $this->membersByBatch(),
            'members_by_country' => $this->membersByCountry(),
            'members_by_district' => $this->membersByDistrict(),
            'members_by_profession' => $this->membersByProfession(),
            'event_registration_trend' => $this->eventRegistrationTrend(),
            'donation_trend' => $this->donationTrend(),
        ];
    }

    /**
     * Monthly member registrations over the last 12 months.
     *
     * @return array<int, array{label: string, total: int, approved: int}>
     */
    public function membersOverTime(): array
    {
        $months = collect();
        $start = now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $month = (clone $start)->addMonths($i);
            $months->put($month->format('Y-m'), [
                'label' => $month->format('M Y'),
                'month_key' => $month->format('Y-m'),
                'total' => 0,
                'approved' => 0,
            ]);
        }

        $records = Member::query()
            ->where('created_at', '>=', $start)
            ->select(['id', 'status', 'created_at'])
            ->get();

        foreach ($records as $member) {
            $key = $member->created_at?->format('Y-m');
            if ($key && $months->has($key)) {
                $item = $months->get($key);
                $item['total']++;
                if ($member->status === MemberStatus::Approved) {
                    $item['approved']++;
                }
                $months->put($key, $item);
            }
        }

        return $months->values()->all();
    }

    /**
     * Top batches by approved membership count.
     *
     * @return array<int, array{label: string, year: int, count: number}>
     */
    public function membersByBatch(): array
    {
        return Batch::query()
            ->orderByDesc('members_count')
            ->limit(10)
            ->get(['name', 'ssc_year', 'members_count'])
            ->map(fn (Batch $b) => [
                'label' => (string) $b->ssc_year,
                'name' => $b->name,
                'year' => (int) $b->ssc_year,
                'count' => (int) $b->members_count,
            ])
            ->all();
    }

    /**
     * Geographic distribution across countries.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function membersByCountry(): array
    {
        return Member::query()
            ->approved()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->select('country', DB::raw('count(*) as aggregate'))
            ->groupBy('country')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'label' => (string) $r->country,
                'count' => (int) $r->aggregate,
            ])
            ->all();
    }

    /**
     * Geographic distribution across Bangladesh districts.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function membersByDistrict(): array
    {
        return Member::query()
            ->approved()
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->select('district', DB::raw('count(*) as aggregate'))
            ->groupBy('district')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'label' => (string) $r->district,
                'count' => (int) $r->aggregate,
            ])
            ->all();
    }

    /**
     * Distribution of alumni across professions / occupations.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function membersByProfession(): array
    {
        return Member::query()
            ->approved()
            ->whereNotNull('occupation')
            ->where('occupation', '!=', '')
            ->select('occupation', DB::raw('count(*) as aggregate'))
            ->groupBy('occupation')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'label' => (string) $r->occupation,
                'count' => (int) $r->aggregate,
            ])
            ->all();
    }

    /**
     * Registration counts across the last 6 published events.
     *
     * @return array<int, array{label: string, registrations: int, attendees: int}>
     */
    public function eventRegistrationTrend(): array
    {
        return Event::query()
            ->whereIn('status', [EventStatus::Published, EventStatus::RegistrationOpen, EventStatus::RegistrationClosed, EventStatus::Completed])
            ->withCount([
                'registrations',
                'registrations as attended_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            ])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'title', 'slug'])
            ->map(fn (Event $e) => [
                'label' => mb_strimwidth((string) $e->title, 0, 20, '...'),
                'full_title' => (string) $e->title,
                'registrations' => (int) $e->registrations_count,
                'attendees' => (int) $e->attended_count,
            ])
            ->all();
    }

    /**
     * Monthly donation volumes in BDT over the last 12 months.
     *
     * @return array<int, array{label: string, amount: float, count: int}>
     */
    public function donationTrend(): array
    {
        $months = collect();
        $start = now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $month = (clone $start)->addMonths($i);
            $months->put($month->format('Y-m'), [
                'label' => $month->format('M Y'),
                'month_key' => $month->format('Y-m'),
                'amount' => 0.0,
                'count' => 0,
            ]);
        }

        $donations = Donation::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $start)
            ->select(['amount', 'created_at'])
            ->get();

        foreach ($donations as $donation) {
            $key = $donation->created_at?->format('Y-m');
            if ($key && $months->has($key)) {
                $item = $months->get($key);
                $item['amount'] += (float) $donation->amount;
                $item['count']++;
                $months->put($key, $item);
            }
        }

        return $months->values()->all();
    }
}
