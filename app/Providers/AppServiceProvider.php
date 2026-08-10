<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Gate::define('manage-master-data', fn ($user): bool => $user->role->code === 'super_admin');
        /** الإعدادات and the moderator accounts stay with the desk; the rest of the centre opens to moderators too, narrowed to what they were assigned. */
        Gate::define('manage-information-lookups', fn ($user): bool => in_array($user->role->code, config('information.desk_roles'), true));
        Gate::define('manage-information-moderators', fn ($user): bool => in_array($user->role->code, config('information.desk_roles'), true));
        Gate::define('access-information-desk', fn ($user): bool => in_array($user->role->code, config('information.admin_roles'), true));
        Gate::define('manage-human-resources-settings', fn ($user): bool => in_array($user->role->code, ['super_admin', 'hr_manager'], true));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('government-login', fn (Request $request) => Limit::perMinute(5)->by(
            $request->ip().'|'.$request->string('username')->lower(),
        ));
        RateLimiter::for('applications', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
    }
}
