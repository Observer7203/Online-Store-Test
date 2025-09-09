<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttributePolicy
{
    public function manage(User $user,)
    {
        // only allow if the user is an admin
        return $user->is_admin === true;
    }
}
