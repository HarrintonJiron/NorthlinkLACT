<?php

namespace App\Providers;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Policies\MilkCollectionPolicy;
use App\Modules\Producers\Policies\ProducerPolicy;
use App\Modules\Producers\Policies\RoutePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn (): Password => Password::min((int) config('auth_security.password.min_length'))
            ->max((int) config('auth_security.password.max_length'))
            ->mixedCase()
            ->numbers()
            ->symbols());

        Gate::policy(Route::class, RoutePolicy::class);
        Gate::policy(Producer::class, ProducerPolicy::class);
        Gate::policy(MilkCollection::class, MilkCollectionPolicy::class);
    }
}
