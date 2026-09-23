<?php

namespace App\Policies;

use App\Models\User;

class SuperAdminModulePolicy
{
    public function access(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
