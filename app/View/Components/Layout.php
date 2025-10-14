<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class Layout extends Component
{
    public string $title;
    public string $pretitle;
    public ?string $appName;
    public ?string $appVersion;
    public ?string $appLastCommit;
    public ?string $userName;

    /**
     * Create a new component instance.
     */
    public function __construct(string $title = 'Dashboard', string $pretitle = '') {
        $this->title = $title;
        $this->pretitle = $pretitle;

        // Optional contextual data
        $this->appName = config('app.name');
        $this->appLastCommit = trim(exec('git describe --tags --always') ?: '');
        $this->appVersion = config('app.version');
        $this->userName = Auth::user()?->name ?? 'Guest';
    }


    public function render(): View|Closure|string
    {
        return view('components.layout');
    }
}
