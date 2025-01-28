<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\RoleUser>
 */
class RoleUserFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'role_id' => Role::factory(),
            'user_id' => User::factory()
        ];
    }
}
