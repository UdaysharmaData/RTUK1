<?php

namespace App\Services\SocialiteMultiTenancySupport;

use App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException;
use App\Services\SocialiteMultiTenancySupport\Traits\SocialitePlusTrait;
use Illuminate\Contracts\Container\Container;
use Laravel\Socialite\One\TwitterProvider;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\BitbucketProvider;
use Laravel\Socialite\Two\FacebookProvider;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\GitlabProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\LinkedInProvider;
use Laravel\Socialite\Two\TwitterProvider as TwitterOAuth2Provider;
use League\OAuth1\Client\Server\Twitter as TwitterServer;

class SocialiteMultiTenantManager extends SocialiteManager
{
    use SocialitePlusTrait;
    /**
     * @var string
     */
    private string $platform;

    /**
     * @param Container $container
     * @param string|null $platformKey
     * @throws InvalidPlatformException
     */
    public function __construct(Container $container, string $platformKey = null)
    {
        parent::__construct($container);
        $this->platform = $this->getRequestPlatform($platformKey);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGithubDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.github");

        return $this->buildProvider(
            GithubProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createFacebookDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.facebook");

        return $this->buildProvider(
            FacebookProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGoogleDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.google");

        return $this->buildProvider(
            GoogleProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createLinkedinDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.linkedin");

        return $this->buildProvider(
            LinkedInProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createBitbucketDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.bitbucket");

        return $this->buildProvider(
            BitbucketProvider::class, $config
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createGitlabDriver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.gitlab");

        return $this->buildProvider(
            GitlabProvider::class, $config
        )->setHost($config['host'] ?? null);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\One\AbstractProvider|AbstractProvider|TwitterProvider
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createTwitterDriver(): \Laravel\Socialite\One\AbstractProvider|AbstractProvider|TwitterProvider
    {
        $config = $this->config->get("services.$this->platform.twitter");

        if (($config['oauth'] ?? null) === 2) {
            return $this->createTwitterOAuth2Driver();
        }

        return new TwitterProvider(
            $this->container->make('request'), new TwitterServer($this->formatConfig($config))
        );
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     */
    protected function createTwitterOAuth2Driver(): AbstractProvider
    {
        $config = $this->config->get("services.$this->platform.twitter") ?? $this->config->get("services.$this->platform.twitter-oauth-2");

        return $this->buildProvider(
            TwitterOAuth2Provider::class, $config
        );
    }
}
