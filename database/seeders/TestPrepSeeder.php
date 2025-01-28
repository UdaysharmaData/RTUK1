<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use App\Models\Passport\Client;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\Role;
use App\Modules\Setting\Models\Site;
use App\Enums\PredefinedApiClientEnum;

class TestPrepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::createDefaults();

        Site::create([
            'domain' => PredefinedApiClientEnum::RunThroughHub->value,
            'name' => PredefinedApiClientEnum::RunThroughHub->name,
            'code' => 'rthub',
            'status' => Site::ACTIVE
        ]);

        ApiClient::create([
            'name' => PredefinedApiClientEnum::RunThroughHub->name,
            'host' => PredefinedApiClientEnum::RunThroughHub->value,
            'is_active' => true,
            'site_id' => Site::firstWhere('domain', PredefinedApiClientEnum::RunThroughHub->value)->id,
        ]);

        Client::create([
            'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
            'name' => 'RunThrough',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'user_id' => null,
            'revoked' => false,
            'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
            'provider' => null
        ]);
    }
}
