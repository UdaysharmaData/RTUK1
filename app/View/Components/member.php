<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class member extends Component
{
    /**
     * The member's name.
     *
     * @var string
     */
    public string $name;

    /**
     * The member's description.
     *
     * @var string
     */
    public string $description;

    /**
     * The member's avatar.
     *
     * @var string
     */
    public string $avatar;

    /**
     * The member's team.
     *
     * @var string
     */
    public string $team;

    /**
     * The site used for branding.
     *
     * @var string
     */
    public string $dataSite;    

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param string $description
     * @param string $avatar
     * @param string $team
     * @param string $site
     * @return void
     */
    public function __construct(string $name, string $description, string $avatar, string $team, string $dataSite='')
    {
        $this->name = $name;
        $this->description = $description;
        $this->avatar = $avatar;
        $this->team = $team;
        $this->dataSite = $dataSite;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|string|Closure
    {
        return view('components.member');
    }
}
