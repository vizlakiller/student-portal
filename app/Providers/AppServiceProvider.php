<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        // Only admins may manage staff accounts. Used by the "can:admin"
        // route middleware and by @can('admin') in the views.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Use our own pagination links (resources/views/partials/pagination.blade.php).
        Paginator::defaultView('partials.pagination');
    }
}
