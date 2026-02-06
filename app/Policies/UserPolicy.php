<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null; 
    }

    public function register(?User $user): bool
    {
        return $user?->can('register') ?? true;
    }

    public function login(?User $user): bool
    {
        return $user?->can('login') ?? true;
    }

    public function logout(User $user): bool
    {
        return $user->can('logout');
    }
}
