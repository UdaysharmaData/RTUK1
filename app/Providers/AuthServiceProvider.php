<?php

namespace App\Providers;

use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Modules\User\Models\Profile;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\ProfilePolicy;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
         'App\Models\Model' => 'App\Policies\ModelPolicy',
        Profile::class => ProfilePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->addPassportConfigurations();
    }

    /**
     * @return void
     */
    private function addPassportConfigurations(): void
    {
        if (!$this->app->routesAreCached()) {
            Passport::routes();
        }

        Passport::hashClientSecrets();
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        Passport::useClientModel(Client::class);
        Passport::useTokenModel(Token::class);
    }
}
