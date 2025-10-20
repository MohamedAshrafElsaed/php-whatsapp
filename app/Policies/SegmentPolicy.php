<?php

namespace App\Policies;

use App\Models\Segment;
use App\Models\User;

class SegmentPolicy
{
    /**
     * Determine if the user can view any segments
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the segment
     */
    public function view(User $user, Segment $segment): bool
    {
        return $user->id === $segment->user_id;
    }

    /**
     * Determine if the user can create segments
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the segment
     */
    public function update(User $user, Segment $segment): bool
    {
        return $user->id === $segment->user_id;
    }

    /**
     * Determine if the user can delete the segment
     */
    public function delete(User $user, Segment $segment): bool
    {
        return $user->id === $segment->user_id;
    }
}
