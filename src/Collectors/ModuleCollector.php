<?php

namespace Nimblephp\debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class ModuleCollector extends DataCollector implements Renderable
{

    private array $moduleRegister;

    public function __construct(array $moduleRegister)
    {
        $this->moduleRegister = $moduleRegister;
    }

    public function collect()
    {
        $messages = $this->getMessages();

        return array(
            'count' => count($messages),
            'messages' => $messages
        );
    }

    public function getMessages()
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
            "$name" => [
                "icon" => "user",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "$name.messages",
                "default" => "{}"
            ],
            "$name:badge" => array(
                "map" => "$name.count",
                "default" => "null"
            )
        ];
    }
}