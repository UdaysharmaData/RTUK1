<?php

namespace Database\Seeders;

use Schema;
use Illuminate\Database\Seeder;
use App\Enums\BoolActiveInactiveEnum;
use App\Enums\TwoFactorAuthMethodEnum;
use App\Modules\User\Models\TwoFactorAuthUser;
use App\Modules\User\Models\TwoFactorAuthMethod;

class TwoFactorAuthMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->truncateTables();

        foreach ($this->factory() as $method) {
            TwoFactorAuthMethod::create([
                'name' => $method['name'],
                'display_name' => $method['display_name'],
                'description' => $method['description']
            ]);
        }
        echo PHP_EOL . 'seeded';
    }

    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        TwoFactorAuthUser::truncate();
        TwoFactorAuthMethod::truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * default two-factor authentication methods
     * @return array[]
     */
    private function factory(): array
    {
        return [
            [
                'name' => TwoFactorAuthMethodEnum::Email2Fa,
                'display_name' => 'Email Verification',
                'description' => "A two-factor authentication code will be sent to your email",
            ],
            [
                'name' => TwoFactorAuthMethodEnum::Sms2Fa,
                'display_name' => 'SMS Verification',
                'description' => "A two-factor authentication code will be sent to your email",
            ],
            [
                'name' => TwoFactorAuthMethodEnum::Google2Fa,
                'display_name' => 'Google Authenticator',
                'is_active' => BoolActiveInactiveEnum::Inactive,
                'description' => "Use google authenticator app to get two-factor authentication codes when prompted",
            ]
        ];
    }
}
