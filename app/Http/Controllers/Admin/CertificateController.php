<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Member;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
        ];

        $certificates = Certificate::query()
            ->with(['member:id,ulid,full_name,membership_no', 'event:id,title'])
            ->when($filters['q'] !== '', fn ($query) => $query->where('recipient_name', 'like', '%'.$filters['q'].'%')->orWhere('certificate_no', 'like', '%'.$filters['q'].'%'))
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/certificates/index', [
            'certificates' => Paginated::from($certificates, fn (Certificate $c): array => [
                'id' => $c->id,
                'ulid' => $c->ulid,
                'certificate_no' => $c->certificate_no,
                'recipient_name' => $c->recipient_name,
                'title' => $c->title,
                'type' => $c->type,
                'issue_date' => $c->issue_date->toDateString(),
                'qr_token' => $c->qr_token,
                'member' => $c->member ? [
                    'ulid' => $c->member->ulid,
                    'full_name' => $c->member->full_name,
                    'membership_no' => $c->member->membership_no,
                ] : null,
                'event' => $c->event ? [
                    'title' => $c->event->title,
                ] : null,
                'verify_url' => route('verify.certificate', ['ulid' => $c->ulid, 'token' => $c->qr_token], false),
                'created_at' => $c->created_at?->toIso8601String(),
            ]),
            'filters' => array_map(fn (string $v): ?string => $v === '' ? null : $v, $filters),
            'events' => Event::query()->latest('id')->take(20)->get(['id', 'title']),
            'members' => Member::query()->approved()->latest('id')->take(30)->get(['id', 'full_name', 'membership_no']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'issue_date' => ['required', 'date'],
            'member_id' => ['nullable', 'exists:members,id'],
            'event_id' => ['nullable', 'exists:events,id'],
        ]);

        Certificate::create($validated);

        return back()->with('success', 'Digital certificate issued successfully.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $certificate->delete();

        return back()->with('success', 'Certificate revoked.');
    }
}
