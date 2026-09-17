<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CrmActivityType;
use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\Member;
use App\Services\Crm\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Recording a call, an email, a meeting or a note.
 *
 * `system` is NOT accepted from a request. Those rows are written by services
 * when something actually happened — a verification, a registration, a
 * payment — and letting a form post one would let anybody fabricate a history
 * the platform appears to vouch for.
 *
 * @see docs/05-modules.md section 6
 */
class CrmActivityController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activities,
    ) {}

    /**
     * Log an activity against a contact.
     */
    public function storeForContact(Request $request, CrmContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $this->record($request, $contact);

        return back()->with('success', __('admin.crm.activity_logged'));
    }

    /**
     * Log an activity against a member directly — someone who never was a
     * prospect still gets called.
     */
    public function storeForMember(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $this->record($request, $member);

        return back()->with('success', __('admin.crm.activity_logged'));
    }

    private function record(Request $request, Model $subject): void
    {
        $validated = $request->validate([
            // `system` is deliberately absent from this list.
            'type' => ['required', 'string', 'in:note,call,email,meeting,task'],
            'subject_line' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:80'],
            // Backdating a call made yesterday is legitimate; postdating one
            // is not.
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $this->activities->log(
            subject: $subject,
            type: CrmActivityType::from($validated['type']),
            subjectLine: $validated['subject_line'] ?? null,
            body: $validated['body'] ?? null,
            outcome: $validated['outcome'] ?? null,
            user: $request->user(),
            occurredAt: isset($validated['occurred_at'])
                ? new \DateTimeImmutable($validated['occurred_at'])
                : null,
        );
    }
}
