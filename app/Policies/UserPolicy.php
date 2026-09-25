<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool { return $actor->hasRole('admin'); }
    public function create(User $actor): bool { return $actor->hasRole('admin'); }
    public function update(User $actor, User $subject): bool { return $actor->hasRole('admin'); }
}
