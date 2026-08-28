<?php

namespace App\Modules\Producers\Policies;

use App\Models\User;
use App\Modules\Producers\Models\MilkCollection;

class MilkCollectionPolicy
{
    public function view(User $user, MilkCollection $collection): bool
    {
        return $user->companies()->where('companies.id', $collection->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_collections');
    }

    public function update(User $user, MilkCollection $collection): bool
    {
        return $user->hasPermission('edit_collections')
            && $user->companies()->where('companies.id', $collection->company_id)->exists();
    }

    public function delete(User $user, MilkCollection $collection): bool
    {
        return $user->hasPermission('delete_collections')
            && $user->companies()->where('companies.id', $collection->company_id)->exists();
    }
}
