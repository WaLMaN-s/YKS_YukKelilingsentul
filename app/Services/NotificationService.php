<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function toUser(User|int $user, string $title, ?string $body = null, ?string $url = null): void
    {
        UserNotification::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }

    /** Kirim ke semua admin yang punya salah satu permission. */
    public function toAdmins(array $permissions, string $title, ?string $body = null, ?string $url = null): void
    {
        $this->adminsWith($permissions)->each(fn (User $u) => $this->toUser($u, $title, $body, $url));
    }

    /** @return Collection<int, User> */
    public function adminsWith(array $permissions): Collection
    {
        $slugs = collect(config('permissions.roles'))
            ->filter(function (array $perms) use ($permissions) {
                return in_array('*', $perms, true) || count(array_intersect($perms, $permissions)) > 0;
            })
            ->keys()
            ->all();

        return User::query()
            ->where('is_blocked', false)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', $slugs))
            ->get();
    }
}
