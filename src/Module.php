<?php

namespace NimblePHP\Debugbar;

use NimblePHP\Debugbar\Event\DebugbarEventListener;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{

    public function getName(): string
    {
        return 'NimblePHP Debugbar';
    }

    public function register(): void
    {
        DebugbarEventListener::register();
    }

}
