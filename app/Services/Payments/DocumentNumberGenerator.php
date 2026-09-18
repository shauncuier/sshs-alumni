<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Receipt and invoice numbers.
 *
 * `RCP-2026-0001`, `INV-2026-0001`. Sequential within a calendar year, which
 * is what a Bangladeshi auditor expects and what makes a gap visible.
 *
 * Generated inside a transaction with a row lock, because two administrators
 * recording cash at the same desk at the same moment is not a hypothetical —
 * and a duplicate receipt number is the kind of error that is discovered a
 * year later by someone who cannot fix it.
 *
 * Latin digits in all cases, so a number can be read over the phone and
 * searched for.
 *
 * @see docs/09-payments.md section 5
 */
class DocumentNumberGenerator
{
    public function receipt(?int $year = null): string
    {
        return $this->next('receipt_no', (string) config('payments.receipt_prefix', 'RCP'), $year);
    }

    public function invoice(?int $year = null): string
    {
        return $this->next('invoice_no', (string) config('payments.invoice_prefix', 'INV'), $year);
    }

    /**
     * The next number in the series for a column.
     *
     * Reads the highest existing number for the year rather than counting
     * rows: a refunded payment keeps its receipt number, so a count would
     * eventually collide.
     */
    private function next(string $column, string $prefix, ?int $year): string
    {
        $year ??= (int) now()->year;
        $stem = "{$prefix}-{$year}-";

        return DB::transaction(function () use ($column, $stem): string {
            /** @var string|null $highest */
            $highest = Payment::query()
                ->lockForUpdate()
                ->where($column, 'like', $stem.'%')
                ->orderByDesc($column)
                ->value($column);

            $sequence = $highest === null
                ? 1
                : ((int) substr($highest, strlen($stem))) + 1;

            return $stem.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
