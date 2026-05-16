<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class MiddlewareCollector extends DataCollector implements Renderable
{
    public static function getMiddlewares(): array
    {
        if (!isset(Kernel::$middlewareManager)) {
            return [];
        }

        $middlewares = Kernel::$middlewareManager->getList();
        $result = [];

        foreach ($middlewares as $middleware) {
            if (!is_array($middleware)) {
                continue;
            }

            $namespace = (string)($middleware['namespace'] ?? '');

            if ($namespace === '') {
                continue;
            }

            if (!class_exists($namespace) && !interface_exists($namespace) && !trait_exists($namespace)) {
                continue;
            }

            $result[] = $middleware;
        }

        return $result;
    }

    public function collect(): array
    {
        $middlewares = self::getMiddlewares();
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
