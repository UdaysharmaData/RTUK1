<?php

namespace Database\Seeders;

use Str;
use Illuminate\Database\Seeder;
use App\Models\Passport\Client;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class RunThroughSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->preSeedOperations();

        if (File::exists('storage/logs/dataimport.log')) File::delete('storage/logs/dataimport.log');

        $site = Site::create([ // RunThrough site
            'ref' => Str::orderedUuid(),
            'domain' => 'runthrough.co.uk',
            'name' => 'RunThrough',
            'code' => 'runthrough',
            'status' => 1
        ]);

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            PermissionRoleSeeder::class,
            RunThroughInitSeeder::class
        ]);
    }

    /**
     * add list of
     * @return void
     */
    private function preSeedOperations(): void
    {
        Artisan::call('cache:clear');

        if (File::exists(public_path('images/default-avatar.png')))
            File::copy(public_path('images/default-avatar.png'), storage_path('app/public/uploads/media/images/default-avatar.png'));

        $this->createTestOauthClient();
    }

    /**
     * @return void
     */
    private function createTestOauthClient()
    {
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