<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class EventCollector extends DataCollector implements Renderable
{
    public static function getEvents(): array
    {
        if (!method_exists(Kernel::class, 'getEventDispatcher')) {
            return [];
        }

        $dispatcher = Kernel::getEventDispatcher();

        if (!is_object($dispatcher) || !method_exists($dispatcher, 'getListeners')) {
            return [];
        }

        $listeners = $dispatcher->getListeners();

        if (!is_array($listeners)) {
            return [];
        }

        $result = [];

        foreach ($listeners as $index => $listenerData) {
            if (!is_array($listenerData) || !isset($listenerData['event'])) {
                continue;
            }

            $eventName = is_string($listenerData['event']) ? $listenerData['event'] : '';

            if ($eventName === '') {
                continue;
            }

            $listener = $listenerData['listener'] ?? null;
            $priority = isset($listenerData['priority']) ? (string)$listenerData['priority'] : '';

            $result[$eventName . '#' . ((int)$index + 1)] = [
                'event' => $eventName,
                'listener' => self::normalizeListener($listener),
                'priority' => $priority,
            ];
        }

        return $result;
    }

    public function collect(): array
    {
        $events = self::getEvents();

        return [
            'data' => $events,
            'count' => count($events),
            'key_map' => [
                'event' => 'Event',
                'listener' => 'Listener',
                'priority' => 'Priority'
            ]
        ];
    }

    public function getName(): string
    {
        return 'events';
    }

    public function getWidgets(): array
    {
        return [
            "Events" => [
                "icon" => "cogs",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "events",
                "default" => "{}"
            ],
            "Events:badge" => [
                "map" => "events.count",
                "default" => 0
            ]
        ];
    }

    private static function normalizeListener(mixed $listener): string
    {
        if (is_array($listener) && isset($listener[0], $listener[1])) {
            $target = $listener[0];
            $method = (string)$listener[1];

            if (is_object($target)) {
                return $target::class . '::' . $method;
            }

            if (is_string($target)) {
                return $target . '::' . $method;
            }
        }

        if ($listener instanceof \Closure) {
            return 'Closure';
        }

        if (is_object($listener)) {
            return $listener::class;
        }

        if (is_string($listener) && $listener !== '') {
            return $listener;
        }

        if (is_callable($listener)) {
            return 'Callable';
        }

        return get_debug_type($listener);
    }
}
