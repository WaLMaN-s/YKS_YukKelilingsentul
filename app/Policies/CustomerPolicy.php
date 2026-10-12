<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /** Dokumen KTP/SIM hanya untuk pemilik dan admin yang berhak. */
    public function viewDocuments(User $user, Customer $customer): bool
    {
        return $customer->user_id === $user->id || $user->hasAnyPermission(['customer', 'rental']);
    }
}
