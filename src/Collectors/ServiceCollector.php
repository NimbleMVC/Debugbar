<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class ServiceCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];
        $serviceIds = Kernel::$serviceContainer->getRegisteredServices();

        foreach ($serviceIds as $id) {
            try {
                $instance = Kernel::$serviceContainer->get($id);
                $result[$id] = [
                    'namespace' => is_object($instance) ? get_class($instance) : gettype($instance)
                ];
            } catch (\Throwable $e) {
                $result[$id] = [
                    'namespace' => 'Error: ' . $e->getMessage()
                ];
            }
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'namespace' => 'Namespace'
            ]
        ];
    }

    public function getName(): string
    {
        return 'services';
    }

    public function getWidgets(): array
    {
        return [
            "Services" => [
                "icon" => "bolt",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "services",
                "default" => "{}"
            ],
            "Services:badge" => [
                "map" => "services.count",
                "default" => 0
            ]
        ];
    }
}