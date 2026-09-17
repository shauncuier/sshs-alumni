<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketTypeRequest;
use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Http\RedirectResponse;

/**
 * Ticket types for one event.
 *
 * `sold_count` is maintained by EventRegistrar and is never writable here — an
 * administrator correcting a price must not be able to rewrite how many have
 * been sold.
 *
 * @see docs/05-modules.md section 4
 */
class TicketTypeController extends Controller
{
    public function store(TicketTypeRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->ticketTypes()->create([
            ...$request->validated(),
            'currency' => $event->currency,
        ]);

        return back()->with('success', __('common.states.saved'));
    }

    public function update(
        TicketTypeRequest $request,
        Event $event,
        EventTicketType $ticketType,
    ): RedirectResponse {
        $this->authorize('update', $event);

        abort_unless($ticketType->event_id === $event->id, 404);

        $ticketType->update($request->validated());

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Deleting a ticket type that has been sold would orphan those
     * registrations' pricing, so it is deactivated instead — it stops being
     * offered without rewriting what people already bought.
     */
    public function destroy(Event $event, EventTicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $event);

        abort_unless($ticketType->event_id === $event->id, 404);

        if ($ticketType->sold_count > 0) {
            $ticketType->update(['is_active' => false]);

            return back()->with('warning', __('admin.events.ticket_deactivated'));
        }

        $ticketType->delete();

        return back()->with('success', __('admin.events.ticket_deleted'));
    }
}
