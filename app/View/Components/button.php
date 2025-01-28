<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class button extends Component
{
    /**
     * The button label.
     *
     * @var string
     */
    public string $label;

    /**
     * The button link.
     *
     * @var string
     */
    public string $url;

    /**
     * The button background color.
     *
     * @var ?string
     */
    public ?string $backgroundColor;

    /**
     * The button text color.
     *
     * @var string
     */
    public ?string $color;

    /**
     * The width of button.
     *
     * @var string
     */
    public ?string $width;

    /**
     * The button theming.
     *
     * @var string
     */
    public ?string $dataSite;

    /**
     * Create a new component instance.
     *
     * @param string $label
     * @param string $url
     * @param string|null $backgroundColor
     * @param string|null $color
     * @param string|null $width
     * @param string|null $dataSite
     * @return void
     */
    public function __construct(string $label, string $url, ?string $backgroundColor = null, ?string $color = null, ?string $width = null, ?string $dataSite = null)
    {
        $this->label = $label;
        $this->url = $url;
        $this->backgroundColor = $backgroundColor;
        $this->color = $color;
        $this->width = $width;
        $this->dataSite = $dataSite;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}
