<?php

namespace NimblePHP\Debugbar;

use NimblePHP\Debugbar\Middleware\DebugbarMiddleware;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{

    public function getName(): string
    {
        return 'NimblePHP Debugbar';
    }

    public function register(): void
    {
        Kernel::$middlewareManager->add(new DebugbarMiddleware(), -100000);
    }

}