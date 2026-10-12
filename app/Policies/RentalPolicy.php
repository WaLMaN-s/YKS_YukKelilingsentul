<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function view(User $user, Rental $rental): bool
    {
        return $rental->user_id === $user->id
            || $user->hasAnyPermission(['rental', 'payment', 'pickup', 'return']);
    }

    public function manage(User $user, Rental $rental): bool
    {
        return $rental->user_id === $user->id;
    }
}
