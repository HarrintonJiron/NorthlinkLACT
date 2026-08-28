<?php

namespace App\Modules\Producers\Policies;

use App\Models\User;
use App\Modules\Producers\Models\Producer;

class ProducerPolicy
{
    public function view(User $user, Producer $producer): bool
    {
        return $user->hasPermission('view_producers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_producers');
    }

    public function update(User $user, Producer $producer): bool
    {
        return $user->hasPermission('edit_producers');
    }

    public function delete(User $user, Producer $producer): bool
    {
        return $user->hasPermission('delete_producers');
    }
}
