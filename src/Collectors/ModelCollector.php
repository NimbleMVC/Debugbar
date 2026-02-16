<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Debugbar\Debugbar;
use NimblePHP\Framework\Kernel;

class ModelCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];

        foreach (Debugbar::$modelData as $model => $loaded) {
            $result[$model] = [
                'loaded' => $loaded
            ];
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'loaded' => 'Loaded count'
            ]
        ];
    }

    public function getName(): string
    {
        return 'models';
    }

    public function getWidgets(): array
    {
        return [
            "Model" => [
                "icon" => "list",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "models",
                "default" => "{}"
            ],
            "Model:badge" => [
                "map" => "models.count",
                "default" => 0
            ]
        ];
    }
}