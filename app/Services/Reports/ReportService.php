<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Batch;
use App\Models\Donation;
use App\Models\EventCheckin;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MemberVerification;
use App\Models\Payment;
use App\Models\Sponsor;
use App\Models\Volunteer;

class ReportService
{
    /**
     * The list of supported reports.
     *
     * @return array<string, array{title: string, description: string, category: string}>
     */
    public function catalog(): array
    {
        return [
            'members' => [
                'title' => 'Alumni Directory Report',
                'description' => 'Comprehensive directory of alumni with batch, contact, and employment details.',
                'category' => 'Membership',
            ],
            'batches' => [
                'title' => 'Batch Cohort Summary',
                'description' => 'Breakdown of alumni distribution and verification rates by SSC completion year.',
                'category' => 'Membership',
            ],
            'registrations' => [
                'title' => 'Membership Applications Funnel',
                'description' => 'Registration application lifecycle tracking approved, pending, and rejected entries.',
                'category' => 'Membership',
            ],
            'verifications' => [
                'title' => 'Verification Audit Trail',
                'description' => 'Log of all verification state transitions and reviewing committee officers.',
                'category' => 'Security',
            ],
            'event-registrations' => [
                'title' => 'Event Registrations & Tickets',
                'description' => 'Attendee registrations, ticket allotments, and guest counts across all events.',
                'category' => 'Events',
            ],
            'attendance' => [
                'title' => 'Event Gate Attendance Logs',
                'description' => 'QR scanner check-in records, admission timestamps, and gate operators.',
                'category' => 'Events',
            ],
            'payments' => [
                'title' => 'Comprehensive Financial Ledger',
                'description' => 'Audited payment ledger containing receipt numbers, payment gateways, and fees.',
                'category' => 'Finance',
            ],
            'donations' => [
                'title' => 'Charitable Contributions & Donations',
                'description' => 'Disclosed and anonymous donations, associated fundraising campaigns, and totals.',
                'category' => 'Finance',
            ],
            'sponsors' => [
                'title' => 'Corporate & Event Sponsorships',
                'description' => 'Sponsorship contracts, package tiers, invoicing records, and settlement status.',
                'category' => 'Finance',
            ],
            'volunteers' => [
                'title' => 'Volunteer Roster & Team Allocations',
                'description' => 'Active event volunteers, committee assignments, and operational teams.',
                'category' => 'Operations',
            ],
            'engagement' => [
                'title' => 'Community Engagement & Discussions',
                'description' => 'Alumni interaction metrics across forum discussions, comments, and event participation.',
                'category' => 'Community',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headers: string[], rows: array<int, array<string, mixed>>}
     */
    public function generate(string $reportKey, array $filters = []): array
    {
        return match ($reportKey) {
            'members' => $this->membersReport($filters),
            'batches' => $this->batchesReport($filters),
            'registrations' => $this->registrationsReport($filters),
            'verifications' => $this->verificationsReport($filters),
            'event-registrations' => $this->eventRegistrationsReport($filters),
            'attendance' => $this->attendanceReport($filters),
            'payments' => $this->paymentsReport($filters),
            'donations' => $this->donationsReport($filters),
            'sponsors' => $this->sponsorsReport($filters),
            'volunteers' => $this->volunteersReport($filters),
            'engagement' => $this->engagementReport($filters),
            default => abort(404, "Unknown report [{$reportKey}]."),
        };
    }

    private function membersReport(array $filters): array
    {
        $query = Member::query()->with('batch')->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        $headers = ['ID', 'Membership No', 'Full Name', 'SSC Year', 'Mobile', 'Email', 'Blood Group', 'Occupation', 'Status', 'Verified At'];

        $rows = $query->take(500)->get()->map(fn (Member $m): array => [
            'id' => $m->id,
            'membership_no' => $m->membership_no ?? '—',
            'full_name' => $m->full_name,
            'ssc_year' => $m->ssc_year ?? '—',
            'mobile' => $m->mobile ?? '—',
            'email' => $m->email ?? '—',
            'blood_group' => $m->blood_group ?? '—',
            'occupation' => $m->occupation ?? '—',
            'status' => $m->status->value,
            'verified_at' => $m->verified_at?->toDateString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function batchesReport(array $filters): array
    {
        $batches = Batch::query()
            ->withCount([
                'members',
                'members as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->orderBy('ssc_year')
            ->get();

        $headers = ['Batch Year', 'Total Alumni', 'Approved Members', 'Pending Ratio'];

        $rows = $batches->map(function (Batch $b): array {
            $pending = $b->members_count - $b->approved_count;

            return [
                'batch_year' => (string) $b->ssc_year,
                'total_alumni' => (string) $b->members_count,
                'approved_members' => (string) $b->approved_count,
                'pending_ratio' => $b->members_count > 0 ? round(($pending / $b->members_count) * 100, 1).'%' : '0%',
            ];
        })->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function registrationsReport(array $filters): array
    {
        $query = Member::query()->latest('registered_at');
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $headers = ['Applicant Name', 'SSC Year', 'Mobile', 'Status', 'Registered At', 'Verified At'];

        $rows = $query->take(500)->get()->map(fn (Member $m): array => [
            'applicant_name' => $m->full_name,
            'ssc_year' => (string) ($m->ssc_year ?? '—'),
            'mobile' => $m->mobile ?? '—',
            'status' => $m->status->value,
            'registered_at' => $m->registered_at?->toDateTimeString() ?? '—',
            'verified_at' => $m->verified_at?->toDateTimeString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function verificationsReport(array $filters): array
    {
        $query = MemberVerification::query()->with('member')->latest('id');

        $headers = ['Member', 'From Status', 'To Status', 'Note', 'Date'];

        $rows = $query->take(500)->get()->map(fn (MemberVerification $v): array => [
            'member' => $v->member?->full_name ?? '—',
            'from_status' => $v->from_status?->value ?? 'None',
            'to_status' => $v->to_status->value,
            'note' => $v->note ?? '—',
            'date' => $v->created_at?->toDateTimeString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function eventRegistrationsReport(array $filters): array
    {
        $query = EventRegistration::query()->with(['event', 'member', 'ticketType'])->latest('id');

        $headers = ['Registration Code', 'Event', 'Attendee', 'Ticket Type', 'Guests', 'Total Amount', 'Status'];

        $rows = $query->take(500)->get()->map(fn (EventRegistration $r): array => [
            'registration_code' => $r->ticket_number ?? '—',
            'event' => $r->event?->title ?? '—',
            'attendee' => $r->member?->full_name ?? '—',
            'ticket_type' => $r->ticketType?->name ?? 'Standard',
            'guests' => (string) ($r->guests_count ?? 0),
            'total_amount' => (string) $r->total_amount,
            'status' => $r->status->value,
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function attendanceReport(array $filters): array
    {
        $query = EventCheckin::query()->with(['event', 'registration.member', 'operator'])->latest('id');

        $headers = ['Check-in ID', 'Event', 'Attendee', 'Admitted At', 'Operator'];

        $rows = $query->take(500)->get()->map(fn (EventCheckin $c): array => [
            'checkin_id' => (string) $c->id,
            'event' => $c->event?->title ?? '—',
            'attendee' => $c->registration?->member?->full_name ?? '—',
            'admitted_at' => $c->created_at?->toDateTimeString() ?? '—',
            'operator' => $c->operator?->name ?? 'Gate Operator',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function paymentsReport(array $filters): array
    {
        $query = Payment::query()->latest('id');

        $headers = ['Receipt No', 'Amount (BDT)', 'Method', 'Purpose', 'Status', 'Date'];

        $rows = $query->take(500)->get()->map(fn (Payment $p): array => [
            'receipt_no' => $p->receipt_number ?? '—',
            'amount' => (string) $p->amount,
            'method' => $p->gateway?->value ?? 'manual',
            'purpose' => class_basename($p->payable_type ?? 'Payment'),
            'status' => $p->status->value,
            'date' => $p->created_at?->toDateTimeString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function donationsReport(array $filters): array
    {
        $query = Donation::query()->latest('id');

        $headers = ['Donation ID', 'Donor Name', 'Amount (BDT)', 'Cause / Campaign', 'Anonymous', 'Date'];

        $rows = $query->take(500)->get()->map(fn (Donation $d): array => [
            'donation_id' => (string) $d->id,
            'donor_name' => $d->is_anonymous ? 'Anonymous Donor' : ($d->donor_name ?? '—'),
            'amount' => (string) $d->amount,
            'cause' => $d->campaign ?? 'General Fund',
            'anonymous' => $d->is_anonymous ? 'Yes' : 'No',
            'date' => $d->created_at?->toDateTimeString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function sponsorsReport(array $filters): array
    {
        $query = Sponsor::query()->with('package')->latest('id');

        $headers = ['Company / Sponsor', 'Package Tier', 'Amount (BDT)', 'Status', 'Contact Person'];

        $rows = $query->take(500)->get()->map(fn (Sponsor $s): array => [
            'company' => $s->name,
            'package' => $s->package?->name ?? 'Custom',
            'amount' => (string) $s->amount,
            'status' => $s->status->value,
            'contact_person' => $s->contact_person ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function volunteersReport(array $filters): array
    {
        $query = Volunteer::query()->with(['member', 'team'])->latest('id');

        $headers = ['Volunteer Name', 'Batch', 'Team', 'Status', 'Registered Date'];

        $rows = $query->take(500)->get()->map(fn (Volunteer $v): array => [
            'name' => $v->member?->full_name ?? '—',
            'batch' => (string) ($v->member?->ssc_year ?? '—'),
            'team' => $v->team?->name ?? 'General Volunteer',
            'status' => $v->status->value,
            'registered_date' => $v->created_at?->toDateString() ?? '—',
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function engagementReport(array $filters): array
    {
        $query = Member::query()
            ->where('status', 'approved')
            ->withCount(['posts', 'comments'])
            ->orderByDesc('posts_count')
            ->take(500);

        $headers = ['Member', 'Batch', 'Forum Posts', 'Comments'];

        $rows = $query->get()->map(fn (Member $m): array => [
            'member' => $m->full_name,
            'batch' => (string) ($m->ssc_year ?? '—'),
            'posts' => (string) $m->posts_count,
            'comments' => (string) $m->comments_count,
        ])->all();

        return ['headers' => $headers, 'rows' => $rows];
    }
}
