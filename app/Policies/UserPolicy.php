<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function manageUsers(User $user): bool
    {
        return $user->role == $user::ROLE_SUPER_ADMIN;
    }

    public function manageOffices(User $user): bool
    {
        return $user->role == $user::ROLE_SUPER_ADMIN;
    }

    public function manageLocations(User $user): bool
    {
        return $user->role == $user::ROLE_SUPER_ADMIN;
    }

    public function Admin(User $user): bool
    {
        return $user->role == $user::ROLE_SUPER_ADMIN;
    }

    public function Installer(User $user): bool
    {
        return $user->role == $user::ROLE_INSTALLER;
    }

    public function Office(User $user): bool
    {
        return $user->role == $user::ROLE_OFFICE;
    }

    public function Agent(User $user): bool
    {
        return $user->role == $user::ROLE_AGENT;
    }

    public function OfficeOrAgent(User $user): bool
    {
        return $user->role == $user::ROLE_OFFICE || $user->role == $user::ROLE_AGENT;
    }

    public function notInstaller(User $user): bool
    {
        return $user->role !== $user::ROLE_INSTALLER;
    }
}
