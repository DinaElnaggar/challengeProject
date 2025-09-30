<?php

namespace App\Providers;

use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
  

        Gate::define('users.read', [OrganizationPolicy::class, 'usersRead']);
        Gate::define('users.update', [OrganizationPolicy::class, 'usersUpdate']);
        Gate::define('users.delete', [OrganizationPolicy::class, 'usersDelete']);
        Gate::define('users.invite', [OrganizationPolicy::class, 'usersInvite']);
        Gate::define('analytics.read', [OrganizationPolicy::class, 'analyticsRead']);
    }
}

