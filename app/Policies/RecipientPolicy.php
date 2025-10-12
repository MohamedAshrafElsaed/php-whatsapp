<?php

namespace App\Policies;

use App\Models\Recipient;
use App\Models\User;

class RecipientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Recipient $recipient): bool
    {
        return $recipient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Recipient $recipient): bool
    {
        return $recipient->user_id === $user->id;
    }

    public function delete(User $user, Recipient $recipient): bool
    {
        return $recipient->user_id === $user->id;
    }
}
