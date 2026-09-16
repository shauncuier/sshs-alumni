<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Opts a model into the audit trail.
 *
 * AuditObserver writes the rows. This trait declares the relation and the
 * per-model exclusion list.
 *
 * Only CHANGED attributes are recorded, never the whole row — which is what
 * keeps the audit table from becoming a second copy of the database, and keeps
 * password hashes, two-factor secrets and API keys out of it entirely.
 *
 * @see docs/08-security-privacy.md section 8
 */
trait Auditable
{
    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Attributes never written to an audit row.
     *
     * Models override `auditExcludedAttributes()` to add their own; the
     * defaults here are always excluded regardless.
     *
     * @return array<int, string>
     */
    public function auditExcluded(): array
    {
        return array_merge([
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'qr_token',
            'search_blob',
            'updated_at',
        ], $this->auditExcludedAttributes());
    }

    /**
     * Per-model additions to the audit exclusion list.
     *
     * @return array<int, string>
     */
    public function auditExcludedAttributes(): array
    {
        return [];
    }
}
