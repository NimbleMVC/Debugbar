<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Framework\Module\Interfaces\ModuleUpdateInterface;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Routes\Route;

class ModuleCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];
        $modules = ModuleRegister::getAll();

        foreach ($modules as $module) {
            /** @var ModuleInterface $moduleClass */
            $moduleClass = $module['classes']->get('module', null);

            $result[$module['namespace']] = [
                'name' => is_object($moduleClass) ? $moduleClass->getName() : $module['name'],
                'version' => $module['config']->get('pkg_version'),
                'path' => $module['config']->get('path'),
                'classes' => $module['classes']->count(),
                'on_update' => is_object($moduleClass) ? ($moduleClass instanceof ModuleUpdateInterface ? 'Yes' : '') : ''
            ];
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'name' => 'Name',
                'version' => 'Version',
                'path' => 'Path',
                'on_update' => 'Update script',
                'classes' => 'Classes'
            ]
        ];
    }

    public function getName(): string
    {
        return 'modules';
    }

    public function getWidgets(): array
    {
        return [
            "Modules" => [
                "icon" => "box",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "modules",
                "default" => "{}"
            ],
            "Modules:badge" => [
                "map" => "modules.count",
                "default" => 0
            ]
        ];
    }
}
