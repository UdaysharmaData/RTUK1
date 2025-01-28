<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class table extends Component
{
    /**
     * The table header data.
     *
     * @var array
     */
    public array $headers;

    /**
     * The table body data.
     *
     * @var array
     */
    public array $body;

    /**
     * Create a new component instance.
     *
     * @param  array $headers
     * @param array $body
     * @return void
     */
    public function __construct(array $headers, array $body)
    {
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|string|Closure
    {
        return view('components.table');
    }
}
