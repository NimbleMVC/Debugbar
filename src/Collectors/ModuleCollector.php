<?php

namespace Nimblephp\debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Nimblephp\framework\ModuleRegister;

class ModuleCollector extends DataCollector implements Renderable
{

    private array $moduleRegister;

    public function __construct(array $moduleRegister)
    {
        $this->moduleRegister = $moduleRegister;
    }

    public function collect()
    {
        $array = [];

        foreach ($this->moduleRegister as $key => $module) {
            $array[$key] = $this->formatVar($module);
        }

        return $array;
    }

    public function getName()
    {
        return 'module_register';
    }

    public function getWidgets()
    {
        $name = $this->getName();
        return [
            "Modules" => [
                "icon" => "user",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "$name",
                "default" => "{}"
            ]
        ];
    }
}