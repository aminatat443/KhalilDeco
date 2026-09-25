<?php

namespace App\Policies;

use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('marketing.view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('marketing.create_campaign');
    }
}
