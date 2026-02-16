<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\MiddlewareManager;

class MiddlewareCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $middlewares = Kernel::$middlewareManager->getList();
        $result = [];

        foreach ($middlewares as $middleware) {
            $result[$middleware['namespace']] = $middleware;
        }

        return [
            'data' => $result,
            'count' => count($middlewares),
            'key_map' => [
                'priority' => 'Priority',
                'implements' => 'Implements'
            ]
        ];
    }

    public function getName(): string
    {
        return 'middlewares';
    }

    public function getWidgets(): array
    {
        return [
            "Middlewares" => [
                "icon" => "briefcase",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "middlewares",
                "default" => "{}"
            ],
            "Middlewares:badge" => [
                "map" => "middlewares.count",
                "default" => 0
            ]
        ];
    }
}
