<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $auditLog->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return $auditLog->user_id === $user->id;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $auditLog->user_id === $user->id;
    }
}
