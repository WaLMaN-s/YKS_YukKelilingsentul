<?php

namespace App\Policies;

use App\Models\TourBooking;
use App\Models\User;

class TourBookingPolicy
{
    public function view(User $user, TourBooking $booking): bool
    {
        return $booking->user_id === $user->id || $user->hasAnyPermission(['tour', 'payment']);
    }

    public function manage(User $user, TourBooking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
