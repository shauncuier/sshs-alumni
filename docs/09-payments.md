# 09 — Payments & Gateway Abstraction

**No payment gateway is hard-coded.** The platform ships with offline/manual payment recording and a driver abstraction, so bKash, Nagad, SSLCommerz or a card processor can be added later without touching a single caller.

---

## 1. What flows through payments

Four things, one ledger:

| Payable             | Source                                                |
| ------------------- | ----------------------------------------------------- |
| `MembershipFee`     | annual or life membership                             |
| `EventRegistration` | event / reunion ticket                                |
| `Donation`          | public or member donation, optionally campaign-tagged |
| `Sponsor`           | sponsorship package purchase                          |

`payments.payable_type` / `payable_id` is polymorphic. Every report, every dashboard figure and every receipt reads from the same table, so income can never disagree with itself.

## 2. Structure

```
App\Services\Payments\
├── Contracts\
│   └── PaymentGateway            charge() · verify() · refund() · handleWebhook() · supportsRefund()
├── Gateways\
│   └── ManualGateway             the only implementation today
├── PaymentManager                resolves a driver from config/payments.php
├── PaymentRecorder               writes the payment + receipt no + audit + notification
└── ReceiptNumberGenerator
```

### The contract

```php
interface PaymentGateway
{
    public function name(): string;

    /** Begin a charge. Returns a redirect/instruction payload or a completed result. */
    public function charge(Payment $payment, array $context = []): PaymentIntent;

    /** Confirm a payment's real state with the provider. */
    public function verify(Payment $payment): PaymentStatus;

    public function refund(Payment $payment, ?string $reason = null): PaymentStatus;

    public function handleWebhook(Request $request): ?Payment;

    public function supportsRefund(): bool;
}
```

### Resolution

```php
// config/payments.php
return [
    'default'   => env('PAYMENT_GATEWAY', 'manual'),
    'currency'  => env('PAYMENT_CURRENCY', 'BDT'),
    'gateways'  => [
        'manual' => ['driver' => ManualGateway::class],
        // 'bkash' => ['driver' => BkashGateway::class, 'app_key' => env('BKASH_APP_KEY'), …],
    ],
];
```

Adding a gateway = one class implementing the contract + one config entry + its env vars. **No controller, service or React component changes.**

## 3. Manual / offline payments

The association collects most money in cash, by bank transfer, or through mobile financial services settled outside the platform. `ManualGateway` makes that a first-class flow, not a workaround.

An admin with `payments.create` records a payment at `/admin/payments/record`:

- payer (member or CRM contact, or a free-text name)
- what it is for (the payable)
- amount, currency, date received
- method note ("bKash 01712xxxxxx, trx ABC123", "DBBL cheque 004521")
- optional attachment (deposit slip → private disk)

`PaymentRecorder` then, in one transaction:

1. Writes the `payments` row with `gateway = manual`, `status = paid`, `recorded_by = {admin}`.
2. Issues a `receipt_no`.
3. Updates the payable's own status — by calling `markPaid()` on the payable itself, so adding a fifth kind of payable never means editing the recorder.
4. Writes an `audit_logs` row naming the recording admin (the `Auditable` concern on `Payment`).
5. Writes a `crm_activities` `system` row on the payer's timeline.
6. Dispatches `PaymentReceived` to the payer. **(Phase 8 — the notification layer does not exist yet.)**

Every one of those steps is required. Skipping the audit row would make offline cash handling untraceable, which is the single largest financial risk in a volunteer-run organization.

### The `Payable` contract

`MembershipFee`, `EventRegistration`, `Donation` and `Sponsor` each implement `App\Services\Payments\Contracts\Payable`:

```php
amountDue()           what is owed
paymentCurrency()
payerMember()         the alumni record, where there is one
payerName()           what goes on the receipt
paymentDescription()  what it was for, in words
markPaid($payment)    settle itself
markUnpaid($payment)  undo that on a refund
```

The payable settles ITSELF, inside the recorder's transaction. A fee can therefore never read `paid` while the ledger row that paid it is missing, and the reverse cannot happen either.

`markUnpaid()` is deliberately not symmetrical across types: a refunded fee goes back to `pending` (the member still owes it), a refunded sponsorship goes back to `confirmed` (the agreement still stands).

### Waiving is not paying

A volunteer-run association waives fees routinely — a founding member, someone in hardship, a teacher. `membership_fees.waived` records that, and **no payment row is written**, because no money was received and the accounts must not claim otherwise. The reason is required: a waiver with no reason is indistinguishable from an oversight.

## 4. Statuses

```
pending ──> paid ──> refunded
   ├──> failed
   └──> cancelled
```

| Status      | Meaning                                      |
| ----------- | -------------------------------------------- |
| `pending`   | created, money not confirmed                 |
| `paid`      | confirmed; receipt issued; payable activated |
| `failed`    | gateway declined                             |
| `cancelled` | abandoned or withdrawn before payment        |
| `refunded`  | returned; original row preserved             |

**Payments are never soft-deleted and never edited into a different amount.** A mistake is corrected by refunding and re-recording. Both actions are audited. This is what makes the ledger trustworthy.

## 5. Receipts & invoices

- `receipt_no` — issued on transition to `paid`. Format `RCP-{YYYY}-{SEQ}`.
- `invoice_no` — issued for sponsorships, which are invoiced before payment. Format `INV-{YYYY}-{SEQ}`.

Both are generated inside a transaction with a row lock so concurrent writes cannot collide — two administrators recording cash at the same desk at the same moment is not hypothetical, and a duplicate receipt number is the kind of error discovered a year later by somebody who cannot fix it.

The generator reads the **highest existing number** for the year rather than counting rows: a refunded payment keeps its receipt number, so a count would eventually collide.

Both stay in **Latin digits** so they can be quoted over the phone and searched reliably.

The documents themselves are rendered on demand — there are no `invoices` or `receipts` tables. See [02-database-schema.md §1.4](02-database-schema.md).

### PDF and Bangla — a known risk

`barryvdh/laravel-dompdf` renders receipts and invoices. **Bengali conjunct shaping in dompdf is unreliable**, so this is tested early in the phase that builds it rather than discovered at the end.

Mitigation if shaping fails: receipts render Latin-script (English names, `BDT` amounts, Latin numerals) — which is already the norm for financial documents in Bangladesh — and the bilingual member-facing card falls back to browser print-to-PDF, which shapes Bengali correctly. See [16-troubleshooting.md](16-troubleshooting.md).

## 6. Adding a real gateway later

Checklist for whoever does it:

1. Implement `PaymentGateway` in `app/Services/Payments/Gateways/`.
2. Register it in `config/payments.php` with its env vars.
3. Add a webhook route; **verify the provider's signature** before trusting the payload.
4. Make `handleWebhook()` idempotent — providers retry, and a duplicate webhook must not double-credit a payment or double-confirm a registration.
5. Always reconcile with `verify()` before marking `paid`. Never trust a client-side redirect as proof of payment.
6. Store the raw provider payload in `payments.meta` for dispute resolution.
7. Add the gateway's keys to [12-environment.md](12-environment.md) and rotate them on staff change.
8. Write tests against the provider's sandbox — including the failure, timeout and duplicate-webhook paths, which are where real money is lost.

### Bangladesh context

bKash, Nagad and Rocket are the realistic first integrations, most likely via an aggregator such as SSLCommerz or ShurjoPay. All require a registered organization, a settlement bank account and merchant onboarding — a business process, not a code task, which is why the platform ships working without it.

## 7. Membership fees

`membership_fees` rows are generated per member per period (`2026`, `Life`). A fee can be **waived** with a reason — volunteer-run associations routinely waive fees, and forcing a fake payment to represent that would corrupt the ledger.

The member dashboard shows outstanding fees; the finance dashboard shows collection rate by batch.

## 8. Reporting

From the one ledger: payment report, donation report, sponsor report, collection by batch, income by category, refund report, offline-vs-gateway split.

All exportable to CSV/XLSX/PDF under `reports.export`, with every export audited. See [05-modules.md §14](05-modules.md).

## 9. Tests

- A manual payment writes the payment, receipt number, audit row, CRM activity and notification — all of them.
- `payments.create` is required to record a payment; `payments.refund` to refund.
- A member sees only their own payments.
- A refund preserves the original row and sets `refunded_at`.
- Receipt numbers are unique under concurrent writes.
- Paying an event registration flips its `payment_status`.
- Waiving a fee does not create a payment row.
- `PaymentManager` resolves the configured driver and throws clearly on an unknown one.
