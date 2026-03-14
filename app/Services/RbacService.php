<?php

namespace App\Services;

use App\Models\UserModel;

class RbacService
{
    public function __construct(private readonly UserModel $userModel = new UserModel())
    {
    }

    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        $profile = $this->userModel->getUserProfile($userId);
        if (! $profile) {
            return false;
        }

        return in_array($permissionSlug, $profile['permissions'], true);
    }
}
