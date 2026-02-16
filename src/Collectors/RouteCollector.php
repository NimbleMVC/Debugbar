<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Routes\Route;

class RouteCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $routes = Route::getRoutes();
        $result = [];

        foreach ($routes as $path => $route) {
            $controller = $route['controller'] ?? '';
            $method = $route['method'] ?? '';
            $httpMethod = $route['httpMethod'] ?? '';
            $target = trim($controller . '::' . $method, ':');
            $normalizedPath = str_starts_with($path, '/') ? $path : '/' . $path;
            $result[$normalizedPath] = [
                'method' => $httpMethod,
                'handler' => $target
            ];
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'method' => 'HTTP',
                'handler' => 'Controller::method'
            ]
        ];
    }

    public function getName(): string
    {
        return 'routes';
    }

    public function getWidgets(): array
    {
        return [
            "Routes" => [
                "icon" => "link",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "routes",
                "default" => "{}"
            ],
            "Routes:badge" => [
                "map" => "routes.count",
                "default" => 0
            ]
        ];
    }
}
