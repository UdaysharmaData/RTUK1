<?php

namespace App\Services\PasswordProtectionPolicy\Console\Commands;

use Illuminate\Console\Command;

class ClearPasswordHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-history:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Password History';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        $this->info('Getting Users...');
        $model = config('passwordprotectionpolicy.observe.model');

        if (class_exists($model)) {
            $model::chunk(100, fn ($users) => $users->each->deletePasswordHistory());
        }

        $this->info('Password History Cleared!');

        return true;
    }
}
