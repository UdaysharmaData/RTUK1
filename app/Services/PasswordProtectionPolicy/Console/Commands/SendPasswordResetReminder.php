<?php

namespace App\Services\PasswordProtectionPolicy\Console\Commands;

use App\Services\PasswordProtectionPolicy\Notifications\PasswordExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendPasswordResetReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-expiry:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to remind users to update soon to expire passwords';

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
            $model::withExpiringPasswords()
                ->chunk(100, fn ($users) => $users->each->notify(new PasswordExpiringNotification));
        }

        $this->info('Password expiration reminder dispatched!');

        return true;
    }
}
