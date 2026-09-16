<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes the audit trail for models using the Auditable concern.
 *
 * Only CHANGED attributes are recorded, never the whole row. That is what
 * keeps this table from becoming a second copy of the database, and what keeps
 * password hashes, two-factor secrets and QR tokens out of it entirely.
 *
 * @see docs/08-security-privacy.md section 8
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', null, $this->filter($model, $model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->filter($model, $model->getChanges());

        // An update that only touched excluded columns is not worth a row.
        if ($changes === []) {
            return;
        }

        $before = array_intersect_key(
            $this->filter($model, $model->getOriginal()),
            $changes,
        );

        $this->record($model, 'updated', $before, $changes);
    }

    public function deleted(Model $model): void
    {
        $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
            ? 'force_deleted'
            : 'deleted';

        $this->record($model, $action, null, null);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', null, null);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function record(Model $model, string $action, ?array $before, ?array $after): void
    {
        $entity = str(class_basename($model))->snake()->toString();

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => "{$entity}.{$action}",
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
            'user_agent' => str(Request::userAgent() ?? '')->limit(500)->toString() ?: null,
        ]);
    }

    /**
     * Strip attributes the model excludes, plus anything that looks like a
     * secret regardless of configuration — a belt-and-braces guard, because a
     * leaked credential in an append-only table cannot be taken back.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filter(Model $model, array $attributes): array
    {
        /** @var array<int, string> $excluded */
        $excluded = method_exists($model, 'auditExcluded') ? $model->auditExcluded() : [];

        $filtered = array_diff_key($attributes, array_flip($excluded));

        foreach (array_keys($filtered) as $key) {
            $name = (string) $key;

            if (preg_match('/password|secret|token|api_key|recovery/i', $name) === 1) {
                unset($filtered[$key]);
            }
        }

        return $filtered;
    }
}
