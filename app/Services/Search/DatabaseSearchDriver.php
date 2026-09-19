<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Announcement;
use App\Models\Batch;
use App\Models\CrmContact;
use App\Models\Event;
use App\Models\Member;
use App\Models\News;
use App\Models\Page;
use App\Models\User;
use App\Services\Search\Contracts\SearchDriver;

class DatabaseSearchDriver implements SearchDriver
{
    /**
     * Search across all authorized entities and return structured matches.
     *
     * @return array<string, array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>>
     */
    public function search(string $query, User $user, int $limit = 5): array
    {
        $term = trim($query);

        if ($term === '' || mb_strlen($term) < 2) {
            return [];
        }

        $results = [];

        // 1. Members
        if ($user->can('members.view')) {
            $memberMatches = $this->searchMembers($term, $user, $limit);
            if (! empty($memberMatches)) {
                $results['Members'] = $memberMatches;
            }
        }

        // 2. Batches
        if ($user->can('batches.view')) {
            $batchMatches = $this->searchBatches($term, $limit);
            if (! empty($batchMatches)) {
                $results['Batches'] = $batchMatches;
            }
        }

        // 3. Events
        if ($user->can('events.view')) {
            $eventMatches = $this->searchEvents($term, $limit);
            if (! empty($eventMatches)) {
                $results['Events'] = $eventMatches;
            }
        }

        // 4. CRM Contacts
        if ($user->can('crm.view')) {
            $crmMatches = $this->searchCrmContacts($term, $limit);
            if (! empty($crmMatches)) {
                $results['CRM Contacts'] = $crmMatches;
            }
        }

        // 5. News & Editorial
        if ($user->can('content.manage')) {
            $newsMatches = $this->searchNews($term, $limit);
            if (! empty($newsMatches)) {
                $results['News'] = $newsMatches;
            }

            $announcementMatches = $this->searchAnnouncements($term, $limit);
            if (! empty($announcementMatches)) {
                $results['Announcements'] = $announcementMatches;
            }

            $pageMatches = $this->searchPages($term, $limit);
            if (! empty($pageMatches)) {
                $results['Pages'] = $pageMatches;
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchMembers(string $term, User $user, int $limit): array
    {
        $query = Member::query();

        // If batch coordinator without full members.verify, narrow to coordinated batches
        if (! $user->can('members.verify') && $user->hasRole('Batch Coordinator')) {
            $member = $user->member;
            if ($member && $member->batch_id) {
                $query->where('batch_id', $member->batch_id);
            }
        }

        return $query->where(function ($q) use ($term): void {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('membership_no', 'like', "%{$term}%")
                ->orWhere('occupation', 'like', "%{$term}%");
        })
            ->limit($limit)
            ->get(['id', 'ulid', 'full_name', 'email', 'membership_no', 'status', 'ssc_year'])
            ->map(fn (Member $m) => [
                'id' => $m->ulid,
                'title' => (string) $m->full_name,
                'subtitle' => $m->membership_no ?: ($m->email ?: "SSC {$m->ssc_year}"),
                'badge' => $m->status->value,
                'url' => route('admin.members.show', $m),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchBatches(string $term, int $limit): array
    {
        return Batch::query()
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('ssc_year', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'slug', 'name', 'ssc_year', 'members_count'])
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'title' => $b->name,
                'subtitle' => "SSC Cohort {$b->ssc_year}",
                'badge' => "{$b->members_count} members",
                'url' => route('admin.batches.show', $b),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchEvents(string $term, int $limit): array
    {
        return Event::query()
            ->where(function ($q) use ($term): void {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('venue', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'ulid', 'slug', 'title', 'type', 'status', 'starts_at'])
            ->map(fn (Event $e) => [
                'id' => $e->ulid,
                'title' => (string) $e->title,
                'subtitle' => $e->starts_at ? $e->starts_at->format('d M Y') : 'Date TBA',
                'badge' => $e->type?->value ?? $e->status->value,
                'url' => route('admin.events.show', $e),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchCrmContacts(string $term, int $limit): array
    {
        return CrmContact::query()
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('organization_name', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'ulid', 'name', 'organization_name', 'type', 'pipeline_status'])
            ->map(fn (CrmContact $c) => [
                'id' => $c->id,
                'title' => (string) $c->name,
                'subtitle' => $c->organization_name ?: $c->type->value,
                'badge' => $c->pipeline_status->value,
                'url' => route('admin.crm.contacts.show', $c),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchNews(string $term, int $limit): array
    {
        return News::query()
            ->where('title', 'like', "%{$term}%")
            ->limit($limit)
            ->get(['id', 'title', 'category', 'status', 'published_at'])
            ->map(fn (News $n) => [
                'id' => $n->id,
                'title' => (string) $n->title,
                'subtitle' => "Category: {$n->category}",
                'badge' => $n->status->value,
                'url' => route('admin.news.index'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchAnnouncements(string $term, int $limit): array
    {
        return Announcement::query()
            ->where('title', 'like', "%{$term}%")
            ->limit($limit)
            ->get(['id', 'title', 'kind', 'status'])
            ->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => (string) $a->title,
                'subtitle' => "Type: {$a->kind->value}",
                'badge' => $a->status->value,
                'url' => route('admin.announcements.index'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string|int, title: string, subtitle: string|null, badge: string|null, url: string}>
     */
    private function searchPages(string $term, int $limit): array
    {
        return Page::query()
            ->where(function ($q) use ($term): void {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'status'])
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => (string) $p->title,
                'subtitle' => "/p/{$p->slug}",
                'badge' => $p->status->value,
                'url' => route('admin.pages.index'),
            ])
            ->all();
    }
}
