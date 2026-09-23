<?php

namespace App\Policies;

use App\Models\User;

class AdminModulePolicy
{
    public function access(User $user): bool
    {
        return $user->isAdmin();
    }
}
