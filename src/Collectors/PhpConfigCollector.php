<?php

namespace NimblePHP\Debugbar\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use NimblePHP\Framework\Kernel;

class PhpConfigCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $result = [];
        $result['php_version'] = ['' => phpversion()];
        $result['php_sapi'] = ['' => php_sapi_name()];
        $result['loaded_extensions'] = ['value' => count(get_loaded_extensions())];

        $important = [
            'memory_limit', 'max_execution_time', 'post_max_size', 'max_input_vars',
            'upload_max_filesize', 'max_file_uploads', 'display_errors', 'error_log',
            'display_startup_errors', 'error_reporting', 'log_errors', 'default_charset',
            'date.timezone', 'session.name', 'session.cookie_lifetime',
            'allow_url_fopen', 'auto_globals_jit', 'browscap',
            'default_charset', 'default_mimetype', 'default_socket_timeout',
            'disable_functions', 'doc_root', 'file_uploads', 'hard_timeout',
            'input_encoding', 'max_input_nesting_level', 'max_input_time',
            'serialize_precision', 'session.cookie_domain', 'session.auto_start',
            'session.cache_expire', 'session.cache_limiter', 'session.cache_path',
            'session.cookie_httponly', 'session.cookie_lifetime', 'session.cookie_samesite',
            'session.name', 'session.save_path', 'short_open_tag'
        ];
        $iniData = ini_get_all();

        foreach ($important as $key) {
            if (isset($iniData[$key])) {
                $config = $iniData[$key];
                $value = $config['local_value'] ?? $config['global_value'] ?? 'N/A';

                if (is_array($value)) {
                    $value = is_array($value) ? json_encode($value) : $value;
                }

                $local = '';
                $global = '';

                if ($config['local_value'] !== $config['global_value']) {
                    $local = is_array($config['local_value']) ? json_encode($config['local_value']) : $config['local_value'];
                    $global = is_array($config['global_value']) ? json_encode($config['global_value']) : $config['global_value'];
                }

                $result[$key] = [
                    'value' => $value,
                    'local' => $local,
                    'global' => $global
                ];
            }
        }

        return [
            'data' => $result,
            'count' => count($result),
            'key_map' => [
                'value' => 'Value',
                'local' => 'Local',
                'global' => 'Global'
            ]
        ];
    }

    public function getName(): string
    {
        return 'php_config';
    }

    public function getWidgets(): array
    {
        return [
            "PHP Info" => [
                "icon" => "brand-php",
                "widget" => "PhpDebugBar.Widgets.TableVariableListWidget",
                "map" => "php_config",
                "default" => "{}"
            ]
        ];
    }
}