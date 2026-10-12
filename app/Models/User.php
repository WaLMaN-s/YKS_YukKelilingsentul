<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role_id', 'is_blocked'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function tourBookings(): HasMany
    {
        return $this->hasMany(TourBooking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function isAdmin(): bool
    {
        return $this->role && $this->role->slug !== 'customer';
    }

    public function isCustomer(): bool
    {
        return $this->role?->slug === 'customer';
    }

    public function hasPermission(string $permission): bool
    {
        $slug = $this->role?->slug;
        if (! $slug || $slug === 'customer') {
            return false;
        }
        $perms = config("permissions.roles.$slug", []);

        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function scopeCustomers($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('slug', 'customer'));
    }

    public function scopeStaffAccounts($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('slug', '!=', 'customer'));
    }
}
