<?php

namespace App\Providers;

use App\Services\Communication\Contracts\SmsChannel;
use App\Services\Communication\SmsManager;
use App\Services\Search\Contracts\SearchDriver;
use App\Services\Search\DatabaseSearchDriver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsManager::class);

        // Resolving the contract gives the configured driver, so callers type
        // hint SmsChannel and never name a vendor.
        $this->app->bind(
            SmsChannel::class,
            fn ($app): SmsChannel => $app->make(SmsManager::class)->driver(),
        );

        $this->app->bind(
            SearchDriver::class,
            DatabaseSearchDriver::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Inertia props are not an API envelope. Without this, a single
        // resource arrives as `member.data.full_name` rather than
        // `member.full_name`. Paginated collections keep their data/meta/links,
        // because those come from the paginator rather than the wrapper.
        JsonResource::withoutWrapping();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
