<?php

declare(strict_types=1);

namespace App\Providers;

use App\Concerns\Auditable;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\MemberObserver;
use App\Policies\BatchPolicy;
use App\Policies\MemberPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Authorization and model observers.
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureGates();
        $this->registerObservers();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(Batch::class, BatchPolicy::class);
    }

    private function configureGates(): void
    {
        // Super Admin bypasses every check.
        //
        // Returning null (not false) is deliberate: it lets the remaining
        // gates and policies run for everyone else, whereas false would deny
        // outright.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }

    private function registerObservers(): void
    {
        Member::observe(MemberObserver::class);

        // Every model using the Auditable concern is audited, discovered
        // rather than listed — so adding the trait to a new model is all that
        // is needed, and nobody has to remember to register it here.
        foreach ($this->auditableModels() as $model) {
            $model::observe(AuditObserver::class);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function auditableModels(): array
    {
        $models = [];

        foreach (File::files(app_path('Models')) as $file) {
            /** @var class-string $class */
            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            if (in_array(Auditable::class, class_uses_recursive($class), true)) {
                $models[] = $class;
            }
        }

        return $models;
    }
}
