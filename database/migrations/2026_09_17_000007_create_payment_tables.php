<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\FeeStatus;
use App\Enums\PaymentGatewayName;
use App\Enums\PaymentStatus;
use App\Enums\SponsorStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One ledger for every kind of money: membership fees, event registrations,
 * donations and sponsorships all resolve through `payments.payable`, so income
 * can never disagree with itself.
 *
 * There are no separate `invoices` / `receipts` tables — both would only hold
 * a sequential number and a foreign key, and the documents themselves are
 * rendered from the payment on demand.
 *
 * @see docs/02-database-schema.md sections 1.4 and 8, docs/09-payments.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Payables ─────────────────────────────────────────────────────

        Schema::create('membership_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('period_label', 40);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->date('due_at')->nullable();
            $table->string('status', 16)->default(FeeStatus::Pending->value);
            // Volunteer-run associations routinely waive fees. Recording that
            // as a fake payment would corrupt the ledger.
            $table->boolean('waived')->default(false);
            $table->string('waived_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'period_label'], 'membership_fees_member_period_unique');
            $table->index('status', 'membership_fees_status_index');
            $table->index('due_at', 'membership_fees_due_at_index');
        });

        Schema::create('donations', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('donor_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name', 150);
            $table->string('donor_email', 191)->nullable();
            $table->string('donor_phone', 32)->nullable();
            $table->string('campaign', 120)->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->boolean('is_anonymous')->default(false);
            $table->text('message')->nullable();
            $table->text('message_bn')->nullable();
            $table->string('status', 16)->default(DonationStatus::Pending->value);
            // Controls appearance on the public donor wall; anonymous
            // donations are excluded regardless.
            $table->boolean('is_public')->default(true);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'donations_status_index');
            $table->index('campaign', 'donations_campaign_index');
            $table->index('received_at', 'donations_received_at_index');
        });

        Schema::create('sponsorship_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->string('name_bn', 80)->nullable();
            $table->string('tier', 24);
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('BDT');
            $table->text('benefits')->nullable();
            $table->text('benefits_bn')->nullable();
            $table->unsignedSmallInteger('max_slots')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tier', 'sponsorship_packages_tier_index');
        });

        Schema::create('sponsors', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sponsorship_package_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('kind', 16);
            $table->string('name', 150);
            $table->string('name_bn', 150)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 191)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('BDT');
            // Private disk. Never served by a direct URL.
            $table->string('agreement_path', 255)->nullable();
            $table->string('status', 16)->default(SponsorStatus::Pending->value);
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'sponsors_status_index');
            $table->index(['event_id', 'is_public'], 'sponsors_event_public_index');
        });

        // ── The ledger ───────────────────────────────────────────────────
        // No soft deletes: a mistake is corrected by refunding and
        // re-recording, both audited. This is what makes the ledger
        // trustworthy. See docs/09-payments.md section 4.
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('reference', 40)->unique();
            $table->morphs('payable');
            $table->foreignId('payer_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payer_name', 150);
            $table->string('gateway', 32)->default(PaymentGatewayName::Manual->value);
            $table->string('gateway_txn_id', 120)->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('status', 16)->default(PaymentStatus::Pending->value);
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_no', 40)->nullable()->unique();
            $table->string('invoice_no', 40)->nullable()->unique();
            // Set for offline/manual entry — who took the cash.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('gateway', 'payments_gateway_index');
            $table->index('gateway_txn_id', 'payments_gateway_txn_index');
            $table->index('paid_at', 'payments_paid_at_index');
            $table->index(['status', 'paid_at'], 'payments_status_paid_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('sponsorship_packages');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('membership_fees');
    }
};
