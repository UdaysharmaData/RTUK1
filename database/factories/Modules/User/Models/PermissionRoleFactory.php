<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\Role;
use Database\Factories\CustomFactory;
use App\Modules\User\Models\Permission;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\PermissionRole>
 */
class PermissionRoleFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'permission_id' => Permission::factory(),
            'role_id' => Role::factory(),
        ];
    }
}
