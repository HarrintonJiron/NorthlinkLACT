<?php

namespace App\Modules\Producers\Policies;

use App\Models\User;
use App\Modules\Producers\Models\Route;

class RoutePolicy
{
    public function view(User $user, Route $route): bool
    {
        return $user->companies()->where('companies.id', $route->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_routes');
    }

    public function update(User $user, Route $route): bool
    {
        return $user->hasPermission('edit_routes') 
            && $user->companies()->where('companies.id', $route->company_id)->exists();
    }

    public function delete(User $user, Route $route): bool
    {
        return $user->hasPermission('delete_routes')
            && $user->companies()->where('companies.id', $route->company_id)->exists();
    }
}
