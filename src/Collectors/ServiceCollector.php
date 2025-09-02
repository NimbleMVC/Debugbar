<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class ServiceCollector extends DataCollector implements Renderable
{
    public function collect()
    {
        $result = [];
        $serviceIds = Kernel::$serviceContainer->getRegisteredServices();

        foreach ($serviceIds as $id) {
            try {
                $instance = Kernel::$serviceContainer->get($id);
                $result[$id] = is_object($instance) ? get_class($instance) : gettype($instance);
            } catch (\Throwable $e) {
                $result[$id] = 'Error: ' . $e->getMessage();
            }
        }

        return [
            'services' => $result,
            'count' => count($result)
        ];
    }

    public function getName()
    {
        return 'services';
    }

    public function getWidgets()
    {
        return [
            "Services" => [
                "icon" => "cogs",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "services.services",
                "default" => "{}"
            ],
            "Services:badge" => [
                "map" => "services.count",
                "default" => 0
            ]
        ];
    }
}