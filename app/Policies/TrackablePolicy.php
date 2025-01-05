<?php

namespace App\Policies;

use App\Models\Trackable;
use App\Models\User;

class TrackablePolicy
{
    public function own(User $user, Trackable $trackable): bool
    {
//        dd($trackable->user_id);
//        return true;
        // Check if the user owns the related model
        return $trackable->user_id === $user->id;
//        return $job->employer->user->is($user);
    }
}
