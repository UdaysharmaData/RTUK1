<?php

namespace App\Http\Helpers;

use File;
use Exception;
use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Modules\Setting\Enums\SiteCodeEnum;
use App\Enums\RoleNameEnum;
use App\Modules\Setting\Models\Site;
use App\Modules\Setting\Enums\EnvironmentEnum;
use Illuminate\Http\Exceptions\HttpResponseException;

class MailHelper
{
    use SiteTrait,
        Response;

    public $site;

    /**
     * @var string
     */
    public string $logo;

    /**
     * @var array
     */
    public array $footerTitles;

    public string $copyrightText;

    public function __construct($site = null)
    {
        try {
            if ($site) {
                $this->site = $site;
            } else {
                $this->site = static::getSite();

                if (!$this->site) {
                    throw new Exception('The site was not found!');
                }
            }
        } catch (Exception $exception) {
            throw new HttpResponseException($this->error($exception->getMessage(), 406));
        }

        $this->setLogo();
        $this->setFooterTitles();
        $this->setCopyright();
    }

    /**
     * @param $route
     * @return string
     */
    public function portalLink($route): string
    {
        $domain = SiteEnum::environmentWebsite(SiteEnum::tryFrom($this->site->domain), EnvironmentEnum::Portal);
        return "https://$domain/$route";
    }

    /**
     * Get the mail name
     * 
     * @param  string  $address
     * @return string
     */
    public function name(string $address = 'from'): string
    {
        return config('mail.' . $this->site?->code . '.' . $address . '.name');
    }

    /**
     * Get the mail address
     * 
     * @param  string  $address
     * @return string
     */
    public function address(string $address = 'from'): string
    {
        return config('mail.' . $this->site?->code . '.' . $address . '.address');
    }

    /**
     * Get the admins info
     *
     * @param  bool   $all
     * @return mixed
     */
	public function administrators(bool $all = true): mixed
    {
        if (File::exists(config_path('notification/' . $this->site?->code . '.php'))) {
            $emails = config('notification.' . $this->site?->code . '.notifiable_administrators');
            return $this->getUsers(RoleNameEnum::Administrator, $all, $emails);
        }

        return null;
	}

    /**
     * Get the account managers info
     *
     * @param  bool  $all
     * @return mixed
     */
    public function accountManagers(bool $all = true): mixed
    {
        return $this->getUsers(RoleNameEnum::AccountManager, $all);
    }
    

    /**
     * Get the developers info
     *
     * @param  bool  $all
     * @return mixed
     */
    public function developerMembers(bool $all = true): mixed{
 
        return config('mail.' . $this->site->code. '.'.'devloperGroup.address');
    }

    /**
     * Get event manage info
     *
     * @param  bool   $all
     * @return mixed
     */
    public function eventManagers(bool $all = true): mixed
    {
        return $this->getUsers(RoleNameEnum::EventManager, $all);
    }

    /**
     * Get information about STS developer member
     *
     * @return object
     */
    public function developerMember(): object
    {
        return (object)[
            'name' => 'Ave Cesaria',
            'position' => 'Developer',
            'company' => 'Sports Tech Solutions',
            'team' => 'Sports Tech Solutions',
            'avatar' => config('app.images_path').'notifications/developer.png'
        ];
    }

    /**
     * Top executive of the current site
     *
     * @return object
     */
    public function topExecutiveMember(): object
    {
        return (object)[
            'name' => config('notification.' . $this->site?->code . '.top_executive.name'),
            'position' => config('notification.' . $this->site?->code . '.top_executive.position'),
            'company' => $this->site->name,
            'team' => $this->site->name,
            'avatar' => \Storage::disk(config('filesystems.default'))->url(config('notification.' . $this->site?->code . '.top_executive.avatar'))
        ];
    }

    /**
     * Manage of the current site
     *
     * @param $charity
     * @return object|null
     */
    public function charityManagerMember($charity): ?object
    {
        $manager = $charity->charityManager;

        return $manager ? (object)[
            'name' => $manager->user?->salutation_name,
            'position' => 'Account Manager',
            'company' => $this->site->name,
            'team' => $this->site->name,
            'avatar' => $manager->user?->profile?->avatar_url
        ] : null;
    }

    /**
     * A charity member details
     * @param $charity
     * @return object|null
     */
    public function charityMember($charity): ?object
    {
        $user = $charity->charityOwner?->user;

        return $user ? (object)[
            'name' => $user->full_name,
            'position' => 'Representative',
            'company' => $charity->name,
            'team' => $charity->name,
            'avatar' => $charity->logo?->storage_url
        ] : null;
    }

    /**
     * get a user detail
     * @param $role
     * @param bool $all
     * @param null $emails
     * @return mixed
     */
    private function getUsers($role, bool $all = false, $emails = null): mixed
    {
        $role = $this->site->roles()->where('name', $role)->first();
        $users = $role->users();

        if ($all) {
            if ($emails) {
                $users = $users->whereIn('email', $emails);
            }
        } else {
            $users = $users->limit(1);
        }

        return $users->select(['email', 'first_name', 'last_name'])->get();
    }

    /**
     * set the mail logo
     * @return void
     */
    private function setLogo(): void
    {
        $this->logo = $this->site->url . '/assets/images/logo.png';
    }

    /**
     * set the mail footer
     * @return void
     */
    private function setFooterTitles(): void
    {
        $url = $this->site->url;
        $code = $this->site->code;

      
    }

    /**
     * @return void
     */
    private function setCopyright(): void
    {
        $url = $this->site->url;
        $name = $this->site->name;

        $this->copyrightText = "ⓒ All rights reserved <a href='$url/events-timeline' target='_blank' style='font-weight: bold;'
                class='text__primary'>$name Events</a>.We appreciate you!";
    }

}
