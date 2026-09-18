<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CrmActivityType;
use App\Enums\CrmContactType;
use App\Enums\PipelineStage;
use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Services\Crm\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The contact page, and what happens to what people write on it.
 *
 * A MESSAGE BECOMES A CRM CONTACT, not a row in a messages table nobody opens.
 * Somebody writing to an alumni association is almost always a prospective
 * member, a donor, or an alumnus who has lost touch — which is to say they are
 * already the thing the CRM exists to track. Filing the message as an activity
 * on their contact record means the next person who speaks to them can see
 * what they said the first time.
 *
 * An existing contact with the same email is REUSED rather than duplicated,
 * and their message is added to the timeline they already have.
 *
 * Nothing is emailed yet: the notification layer lands in Phase 8, and this is
 * the hook it attaches to. The page says plainly that a person reads these, so
 * nobody is left believing an automatic reply is coming.
 *
 * @see docs/05-modules.md section 6
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activities,
    ) {}

    public function show(): Response
    {
        return Inertia::render('public/contact', [
            'contact' => [
                'email' => setting('contact.email'),
                'phone' => setting('contact.phone'),
                'address' => setting('contact.address'),
                'map_url' => setting('contact.map_url'),
            ],
            'school' => [
                'name_en' => setting('school.name_en'),
                'address' => setting('school.address'),
                'phone' => setting('school.phone'),
                'email' => setting('school.email'),
            ],
            'social' => [
                'facebook' => setting('social.facebook'),
                'youtube' => setting('social.youtube'),
                'linkedin' => setting('social.linkedin'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contact = CrmContact::query()
            ->where('email', $validated['email'])
            ->first();

        if ($contact === null) {
            $contact = CrmContact::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'type' => CrmContactType::Prospect,
                'pipeline_status' => PipelineStage::New,
                'source' => 'website',
            ]);
        }

        // Filed on the timeline the CRM already shows for this person. The
        // subject line is theirs, not ours — a coordinator scanning a list of
        // enquiries needs to see what each one was actually about.
        $this->activities->log(
            subject: $contact,
            type: CrmActivityType::Note,
            subjectLine: $validated['subject'],
            body: $validated['message'],
            meta: ['channel' => 'contact_form'],
        );

        return back()->with('success', __('public.contact.sent'));
    }
}
