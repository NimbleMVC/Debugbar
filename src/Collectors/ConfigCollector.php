<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class ConfigCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];

        foreach ($_ENV as $name => $value) {
            if (str_contains(strtolower($name), 'password')) {
                $value = '*****';
            }

            $result[$name] = ['value' => $value];
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'value' => 'Value'
            ]
        ];
    }

    public function getName(): string
    {
        return 'config';
    }

    public function getWidgets(): array
    {
        return [
            "Config" => [
                "icon" => "table",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "config",
                "default" => "{}"
            ],
            "Config:badge" => [
                "map" => "config.count",
                "default" => 0
            ]
        ];
    }
}