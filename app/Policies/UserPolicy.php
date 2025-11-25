<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy
{
    public function register(?User $user): bool
    {
        return $user === null; 
    }
    public function login(?User $user): bool
    {
        return $user === null; 
    }
    public function logout(?User $user): bool
    {
        return $user !== null; 
    }
}
