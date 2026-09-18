<?php

declare(strict_types=1);

use App\Enums\CrmActivityType;
use App\Enums\DonationStatus;
use App\Enums\FeeStatus;
use App\Enums\PaymentStatus;
use App\Enums\SponsorStatus;
use App\Models\CrmActivity;
use App\Models\Donation;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Models\Payment;
use App\Models\Sponsor;
use App\Models\User;
use App\Services\Payments\DocumentNumberGenerator;
use App\Services\Payments\PaymentRecorder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

/**
 * THE LEDGER.
 *
 * One table for every kind of money, so no two reports can disagree about
 * income. Payments are never edited into a different amount and never deleted:
 * a mistake is corrected by refunding and re-recording, and both are audited.
 * That is the whole reason the ledger is worth reading.
 *
 * @see docs/09-payments.md section 4
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function treasurer(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Finance Manager']);

    return $user;
}

describe('recording money received', function (): void {
    it('writes the ledger row, the receipt and the payable status together', function (): void {
        $member = Member::factory()->approved()->create();

        $fee = MembershipFee::factory()->create([
            'member_id' => $member->id,
            'amount' => 500,
            'status' => FeeStatus::Pending,
        ]);

        $actor = treasurer();

        $payment = app(PaymentRecorder::class)->recordManual(
            payable: $fee,
            recordedBy: $actor,
            method: 'bKash 01712345678, trx ABC123',
        );

        expect($payment->status)->toBe(PaymentStatus::Paid)
            ->and((float) $payment->amount)->toBe(500.0)
            // The receipt is issued here, not posted.
            ->and($payment->receipt_no)->toStartWith('RCP-')
            // Who took the money. Untraceable offline cash is the single
            // largest financial risk in a volunteer-run organization.
            ->and($payment->recorded_by)->toBe($actor->id)
            ->and($payment->meta['method'])->toBe('bKash 01712345678, trx ABC123');

        // The payable marked itself, in the same transaction.
        expect($fee->refresh()->status)->toBe(FeeStatus::Paid);
    });

    it('puts the payment on the payer CRM timeline', function (): void {
        $member = Member::factory()->approved()->create();
        $fee = MembershipFee::factory()->create(['member_id' => $member->id, 'amount' => 500]);

        app(PaymentRecorder::class)->recordManual($fee, treasurer());

        $entry = CrmActivity::query()->where('type', CrmActivityType::System)->sole();

        expect($entry->subject_id)->toBe($member->id)
            ->and($entry->subject_line)->toContain('500');
    });

    it('does not fall over for money with no person behind it', function (): void {
        // An anonymous cash donation in a bucket has no member and no contact.
        // That is a real case.
        $donation = Donation::factory()->create([
            'donor_member_id' => null,
            'crm_contact_id' => null,
            'is_anonymous' => true,
            'amount' => 1000,
            'status' => DonationStatus::Pending,
        ]);

        $payment = app(PaymentRecorder::class)->recordManual($donation, treasurer());

        expect($payment->status)->toBe(PaymentStatus::Paid)
            ->and($donation->refresh()->status)->toBe(DonationStatus::Received)
            ->and(CrmActivity::query()->count())->toBe(0);
    });

    it('refuses money received in the future', function (): void {
        $fee = MembershipFee::factory()->create(['amount' => 500]);

        $this->actingAs(treasurer())
            ->post('/admin/payments', [
                'payable_kind' => 'fee',
                'payable_id' => $fee->id,
                'paid_at' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('paid_at');
    });
});

describe('receipt numbers', function (): void {
    it('runs sequentially within a year', function (): void {
        $numbers = app(DocumentNumberGenerator::class);
        $year = now()->year;

        $first = $numbers->receipt();
        expect($first)->toBe("RCP-{$year}-0001");

        // The generator reads the highest EXISTING number, so a row has to be
        // there for the next call to advance.
        Payment::factory()->create(['receipt_no' => $first]);

        expect($numbers->receipt())->toBe("RCP-{$year}-0002");
    });

    it('does not reuse a refunded payment number', function (): void {
        $year = now()->year;

        Payment::factory()->create(['receipt_no' => "RCP-{$year}-0001"]);
        Payment::factory()->create([
            'receipt_no' => "RCP-{$year}-0002",
            'status' => PaymentStatus::Refunded,
        ]);

        // Counting rows rather than reading the highest would collide here.
        expect(app(DocumentNumberGenerator::class)->receipt())->toBe("RCP-{$year}-0003");
    });

    it('issues invoices in their own series', function (): void {
        $year = now()->year;

        expect(app(DocumentNumberGenerator::class)->invoice())->toBe("INV-{$year}-0001");
    });
});

describe('refunding', function (): void {
    it('preserves the original row and its receipt', function (): void {
        $member = Member::factory()->approved()->create();
        $fee = MembershipFee::factory()->create([
            'member_id' => $member->id,
            'amount' => 500,
            'status' => FeeStatus::Pending,
        ]);

        $recorder = app(PaymentRecorder::class);
        $payment = $recorder->recordManual($fee, treasurer());
        $receipt = $payment->receipt_no;

        $recorder->refund($payment, treasurer(), 'Paid twice by mistake');

        $payment->refresh();

        expect($payment->status)->toBe(PaymentStatus::Refunded)
            // A ledger that erases its mistakes cannot be audited.
            ->and($payment->receipt_no)->toBe($receipt)
            ->and($payment->refunded_at)->not->toBeNull()
            ->and($payment->refund_reason)->toBe('Paid twice by mistake');

        expect(Payment::query()->count())->toBe(1);
    });

    it('puts the payable back', function (): void {
        $fee = MembershipFee::factory()->create([
            'amount' => 500,
            'status' => FeeStatus::Pending,
        ]);

        $recorder = app(PaymentRecorder::class);
        $payment = $recorder->recordManual($fee, treasurer());

        expect($fee->refresh()->status)->toBe(FeeStatus::Paid);

        $recorder->refund($payment, treasurer(), 'Wrong member');

        // Pending, not cancelled: the member still owes it.
        expect($fee->refresh()->status)->toBe(FeeStatus::Pending);
    });

    it('refuses to refund something that was never paid', function (): void {
        $fee = MembershipFee::factory()->create(['amount' => 500]);
        $payment = app(PaymentRecorder::class)->raise($fee);

        expect(fn () => app(PaymentRecorder::class)->refund($payment, treasurer()))
            ->toThrow(RuntimeException::class);
    });

    it('is closed to somebody who can record but not refund', function (): void {
        $fee = MembershipFee::factory()->create(['amount' => 500]);
        $payment = app(PaymentRecorder::class)->recordManual($fee, treasurer());

        $user = User::factory()->create();
        // Membership Manager holds no payments permissions at all.
        $user->syncRoles(['Membership Manager']);

        $this->actingAs($user)
            ->post("/admin/payments/{$payment->ulid}/refund", ['reason' => 'No'])
            ->assertForbidden();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Paid);
    });
});

describe('there is no way to edit or delete a payment', function (): void {
    it('registers no update or destroy route', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'admin/payments'))
            ->map(fn ($route): string => $route->methods()[0].' '.$route->uri())
            ->values()
            ->all();

        // A mistake is corrected by refunding and re-recording. A route to
        // edit one would invite exactly the thing the ledger forbids.
        expect($routes)->not->toContain('PUT admin/payments/{payment}')
            ->and($routes)->not->toContain('DELETE admin/payments/{payment}');
    });
});

describe('sponsors', function (): void {
    it('are invoiced before they pay', function (): void {
        $sponsor = Sponsor::factory()->create([
            'amount' => 50000,
            'status' => SponsorStatus::Confirmed,
        ]);

        $this->actingAs(treasurer())
            ->post("/admin/sponsors/{$sponsor->ulid}/invoice")
            ->assertRedirect();

        $payment = Payment::query()->sole();

        expect($payment->invoice_no)->toStartWith('INV-')
            ->and($payment->receipt_no)->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Pending);
    });

    it('become paid only through the ledger', function (): void {
        $sponsor = Sponsor::factory()->create([
            'amount' => 50000,
            'status' => SponsorStatus::Confirmed,
        ]);

        // Trying to set `paid` through the ordinary update form.
        $this->actingAs(treasurer())
            ->put("/admin/sponsors/{$sponsor->ulid}", ['status' => 'paid'])
            ->assertRedirect();

        expect($sponsor->refresh()->status)->toBe(SponsorStatus::Confirmed)
            ->and(Payment::query()->count())->toBe(0);
    });
});
