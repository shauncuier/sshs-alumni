<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'employment_type' => (string) $request->query('employment_type', ''),
        ];

        $postings = JobPosting::query()
            ->with(['member:id,ulid,full_name,membership_no'])
            ->when($filters['q'] !== '', fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('company_name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['employment_type'] !== '', fn ($query) => $query->where('employment_type', $filters['employment_type']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/jobs/index', [
            'jobs' => Paginated::from($postings, fn (JobPosting $j): array => [
                'id' => $j->id,
                'ulid' => $j->ulid,
                'title' => $j->title,
                'company_name' => $j->company_name,
                'location' => $j->location,
                'workplace_type' => $j->workplace_type,
                'employment_type' => $j->employment_type,
                'salary_range' => $j->salary_range,
                'deadline_at' => $j->deadline_at?->toDateString(),
                'status' => $j->status->value,
                'member' => $j->member ? [
                    'ulid' => $j->member->ulid,
                    'full_name' => $j->member->full_name,
                    'membership_no' => $j->member->membership_no,
                ] : null,
                'created_at' => $j->created_at?->toIso8601String(),
            ]),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'statuses' => ContentStatus::cases(),
        ]);
    }

    public function togglePublish(JobPosting $job): RedirectResponse
    {
        if ($job->status === ContentStatus::Published) {
            $job->update([
                'status' => ContentStatus::Draft,
                'published_at' => null,
            ]);
            $msg = 'Job posting unpublished.';
        } else {
            $job->update([
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ]);
            $msg = 'Job posting published.';
        }

        return back()->with('success', $msg);
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        $job->delete();

        return back()->with('success', 'Job posting removed.');
    }
}
