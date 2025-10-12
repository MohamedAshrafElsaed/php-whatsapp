<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaSession;

class WaSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WaSession $waSession): bool
    {
        return $waSession->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WaSession $waSession): bool
    {
        return $waSession->user_id === $user->id;
    }

    public function delete(User $user, WaSession $waSession): bool
    {
        return $waSession->user_id === $user->id;
    }
}
