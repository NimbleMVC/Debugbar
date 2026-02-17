<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;
use NimblePHP\Debugbar\Debugbar;

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
                    'namespace' => is_object($instance) ? get_class($instance) : gettype($instance),
                    'get' => Debugbar::$serviceData[$id]['get']-1,
                    'set' => Debugbar::$serviceData[$id]['set'],
                    'remove' => Debugbar::$serviceData[$id]['remove'],
                    'has' => Debugbar::$serviceData[$id]['has']
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
                'namespace' => 'Namespace',
                'get' => 'Get count',
                'set' => 'Set count',
                'has' => 'Has count',
                'remove' => 'Remove count'
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