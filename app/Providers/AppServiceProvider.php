<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Producers\Policies\RoutePolicy;
use App\Modules\Producers\Policies\ProducerPolicy;
use App\Modules\Producers\Policies\MilkCollectionPolicy;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\MilkCollection;

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
        Gate::policy(Route::class, RoutePolicy::class);
        Gate::policy(Producer::class, ProducerPolicy::class);
        Gate::policy(MilkCollection::class, MilkCollectionPolicy::class);
    }
}
