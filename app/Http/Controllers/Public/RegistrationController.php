<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Membership\RegisterMember;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MemberLinkType;
use App\Enums\RelationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegistrationStepRequest;
use App\Models\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public membership registration: five steps, validated one at a time.
 *
 * The draft lives in the session so a refresh, a phone call, or a lost
 * connection does not throw away four screens of typing — which matters when
 * much of this audience is registering on a phone.
 *
 * @see docs/05-modules.md section 1
 */
class RegistrationController extends Controller
{
    private const DRAFT_KEY = 'registration.draft';

    private const PASSWORD_KEY = 'registration.password';

    public function start(): RedirectResponse
    {
        return redirect()->route('join.step', ['step' => RegistrationStepRequest::STEPS[0]]);
    }

    public function step(Request $request, string $step): Response|RedirectResponse
    {
        if (! in_array($step, RegistrationStepRequest::STEPS, true)) {
            return redirect()->route('join.start');
        }

        /** @var array<string, mixed> $draft */
        $draft = $request->session()->get(self::DRAFT_KEY, []);

        // A visitor cannot deep-link past a step they have not completed —
        // otherwise the final submit would fail on fields they never saw.
        $furthest = $this->furthestAllowedStep($draft);

        if (array_search($step, RegistrationStepRequest::STEPS, true) > $furthest) {
            return redirect()->route('join.step', [
                'step' => RegistrationStepRequest::STEPS[$furthest],
            ]);
        }

        return Inertia::render('public/join', [
            'step' => $step,
            'steps' => RegistrationStepRequest::STEPS,
            'draft' => $this->safeDraft($draft),
            'options' => [
                'relation_types' => RelationType::options(),
                'genders' => Gender::options(),
                'blood_groups' => BloodGroup::options(),
                'link_types' => MemberLinkType::options(),
                'batches' => Batch::query()
                    ->orderByDesc('ssc_year')
                    ->get(['id', 'name', 'ssc_year'])
                    ->map(fn (Batch $batch): array => [
                        'id' => $batch->id,
                        'label' => $batch->name,
                        'ssc_year' => $batch->ssc_year,
                    ]),
            ],
        ]);
    }

    public function store(
        RegistrationStepRequest $request,
        RegisterMember $register,
        string $step,
    ): RedirectResponse {
        $validated = $request->validated();

        // The password is kept apart from the draft and hashed immediately, so
        // a plaintext password never sits in the session store — which for this
        // application is the database.
        if (isset($validated['password'])) {
            $request->session()->put(self::PASSWORD_KEY, Hash::make($validated['password']));
            unset($validated['password'], $validated['password_confirmation']);
        }

        /** @var array<string, mixed> $draft */
        $draft = $request->session()->get(self::DRAFT_KEY, []);

        $request->session()->put(self::DRAFT_KEY, [
            ...$draft,
            ...$validated,
            'completed_steps' => array_values(array_unique([
                ...($draft['completed_steps'] ?? []),
                $step,
            ])),
        ]);

        $index = (int) array_search($step, RegistrationStepRequest::STEPS, true);
        $next = RegistrationStepRequest::STEPS[$index + 1] ?? null;

        // The final step submits directly. Redirecting to the submit route
        // would mean redirecting a browser to a POST endpoint, which browsers
        // turn into a GET.
        if ($next === null) {
            return $this->submit($request, $register);
        }

        return redirect()->route('join.step', ['step' => $next]);
    }

    /**
     * Create the account. Reached from the final step, never linked directly.
     */
    private function submit(Request $request, RegisterMember $register): RedirectResponse
    {
        /** @var array<string, mixed> $draft */
        $draft = $request->session()->get(self::DRAFT_KEY, []);
        $hashedPassword = $request->session()->get(self::PASSWORD_KEY);

        // Someone arriving here directly, or after the session expired, is
        // sent back to finish rather than shown an error about a form they
        // cannot see.
        if ($draft === [] || ! is_string($hashedPassword)) {
            return redirect()
                ->route('join.start')
                ->with('warning', __('public.join.session_expired'));
        }

        $completed = $draft['completed_steps'] ?? [];
        $required = RegistrationStepRequest::STEPS;

        if (array_diff($required, is_array($completed) ? $completed : []) !== []) {
            return redirect()
                ->route('join.step', ['step' => RegistrationStepRequest::STEPS[$this->furthestAllowedStep($draft)]])
                ->with('warning', __('public.join.incomplete'));
        }

        $register($draft, $hashedPassword);

        $request->session()->forget([self::DRAFT_KEY, self::PASSWORD_KEY]);

        return redirect()->route('join.done');
    }

    public function done(): Response
    {
        return Inertia::render('public/join-done');
    }

    /**
     * The furthest step index a visitor may open, based on what they have
     * actually completed.
     *
     * @param  array<string, mixed>  $draft
     */
    private function furthestAllowedStep(array $draft): int
    {
        $completed = $draft['completed_steps'] ?? [];

        if (! is_array($completed) || $completed === []) {
            return 0;
        }

        $furthest = 0;

        foreach (RegistrationStepRequest::STEPS as $index => $step) {
            if (in_array($step, $completed, true)) {
                $furthest = min($index + 1, count(RegistrationStepRequest::STEPS) - 1);
            }
        }

        return $furthest;
    }

    /**
     * The draft as sent back to the browser — never including anything
     * password-shaped.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function safeDraft(array $draft): array
    {
        unset($draft['password'], $draft['password_confirmation']);

        return $draft;
    }
}
