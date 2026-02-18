<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;
use NimblePHP\Debugbar\Debugbar;

class TemplateCollector extends \DebugBar\DataCollector\TemplateCollector
{

    public function addTemplate(string $name, array $data, ?string $type, ?string $path): void
    {
        $hash = $type . $path . $name . ($this->collect_data ? implode(array_keys($data)) : '');

        if ($this->collect_data === 'keys') {
            $params = array_keys($data);
        } elseif ($this->collect_data) {
            $params = array_map(
                fn($value) => $this->getDataFormatter()->formatVar($value, true),
                $data,
            );
        } else {
            $params = [];
        }

        $template = [
            'name' => $name,
            'param_count' => $this->collect_data ? count($params) : null,
            'params' => $params,
            'start' => microtime(true),
            'type' => $type,
            'hash' => $hash,
        ];

        if ($path && $this->getXdebugLinkTemplate()) {
            $template['xdebug_link'] = $this->getXdebugLink($path);
        }

        if ($this->hasTimeDataCollector()) {
            $this->addTimeMeasure($name, $template['start']);
        }

        $this->templates[] = $template;
    }

}