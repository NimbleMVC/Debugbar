<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Translation\Translation;

class TranslationCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];
        $this->collectData(Translation::getInstance()->getTranslations(), null, $result);

        return [
            'data' => $result,
            'badge' => count($result),
            'key_map' => [
                'value' => 'Value'
            ]
        ];
    }

    public function getName(): string
    {
        return 'translation';
    }

    public function getWidgets(): array
    {
        return [
            'Translations' => [
                'icon' => 'bookmark',
                'widget' => 'PhpDebugBar.Widgets.TableVariableListWidget',
                'map' => 'translation',
                'default' => '{}'
            ],
            'Translations:badge' => [
                'map' => 'translation.badge',
                'default' => '0'
            ]
        ];
    }

    private function collectData(array $data, ?string $parent, array &$result): void
    {
        $parent = is_null($parent) ? '' : ($parent . '.');

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->collectData($value, $parent . $key, $result);
            } else {
                $result[$parent . $key] = [
                    'value' => $value
                ];
            }
        }
    }
}