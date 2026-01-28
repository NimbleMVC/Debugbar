<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class PhpConfigCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $important = [
            'memory_limit', 'max_execution_time', 'post_max_size',
            'upload_max_filesize', 'max_file_uploads', 'display_errors',
            'error_reporting', 'log_errors', 'default_charset',
            'date.timezone', 'session.name', 'session.cookie_lifetime'
        ];

        $data = [];
        $iniData = ini_get_all();

        foreach ($important as $key) {
            if (isset($iniData[$key])) {
                $config = $iniData[$key];
                $value = $config['local_value'] ?? $config['global_value'] ?? 'N/A';

                if ($config['local_value'] !== $config['global_value']) {
                    $value = [
                        'local' => $config['local_value'],
                        'global' => $config['global_value']
                    ];
                }

                $data[$key] = is_array($value) ? json_encode($value) : $value;
            }
        }

        $data['php_version'] = phpversion();
        $data['php_sapi'] = php_sapi_name();
        $data['loaded_extensions'] = count(get_loaded_extensions());

        return $data;
    }

    public function getName(): string
    {
        return 'php_config';
    }

    public function getWidgets(): array
    {
        return [
            "PHP Info" => [
                "icon" => "cogs",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "php_config",
                "default" => "{}"
            ]
        ];
    }
}