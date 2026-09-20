<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\JobPostingRequest;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $query = JobPosting::query()
            ->published()
            ->with(['member.batch']);

        if ($workplaceType = $request->query('workplace_type')) {
            $query->where('workplace_type', $workplaceType);
        }

        if ($employmentType = $request->query('employment_type')) {
            $query->where('employment_type', $employmentType);
        }

        if ($experienceLevel = $request->query('experience_level')) {
            $query->where('experience_level', $experienceLevel);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        $jobs = $query->latest('published_at')->paginate(15)->withQueryString();

        return Inertia::render('member/jobs', [
            'jobs' => JobPostingResource::collection($jobs),
            'filters' => [
                'q' => $request->query('q'),
                'workplace_type' => $request->query('workplace_type'),
                'employment_type' => $request->query('employment_type'),
                'experience_level' => $request->query('experience_level'),
            ],
        ]);
    }

    public function show(JobPosting $job): Response
    {
        $this->authorize('view', $job);

        $job->load(['member.batch']);

        return Inertia::render('member/job-show', [
            'job' => JobPostingResource::make($job),
        ]);
    }

    public function store(JobPostingRequest $request): RedirectResponse
    {
        $member = $request->user()?->member;
        abort_unless($member !== null, 403);

        $member->jobPostings()->create($request->validated());

        return back()->with('success', 'Job posting created successfully.');
    }

    public function update(JobPostingRequest $request, JobPosting $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $job->update($request->validated());

        return back()->with('success', 'Job posting updated successfully.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        $job->delete();

        return back()->with('success', 'Job posting removed successfully.');
    }
}
