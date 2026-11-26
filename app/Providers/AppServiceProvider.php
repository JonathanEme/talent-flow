<?php

namespace App\Providers;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        JsonResource::withoutWrapping();

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        Gate::define('manage-companies', fn (User $user): bool => $user->isGlobalAdmin());
        Gate::define('manage-positions', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-candidates', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-applications', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-interviews', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-offers', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-skills', fn (User $user): bool => $user->isGlobalAdmin());
        Gate::define('submit-interview-feedback', fn (User $user, Interview $interview): bool => $user->isAdmin()
            || ($user->isMember() && $interview->interviewer_id === $user->id));
    }
}
