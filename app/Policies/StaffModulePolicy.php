<?php

namespace App\Policies;

use App\Models\User;

class StaffModulePolicy
{
    public function access(User $user): bool
    {
        return $user->isStaff();
    }
}
