<?php

namespace Nimblephp\debugbar;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\StandardDebugBar;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use Nimblephp\framework\Config;
use Nimblephp\framework\Kernel;

/**
 * Debugbar module
 */
class Debugbar
{

    /**
     * Debugbar instance
     * @var StandardDebugBar
     */
    public static StandardDebugBar $debugBar;

    /**
     * Render head
     * @return string
     */
    public static function renderHead(): string
    {
        return '<script src="/assets/debugbar.js"></script>
        <link rel="stylesheet" href="/assets/debugbar.css">';
    }

    /**
     * Render debugbar
     * @return string
     */
    public static function render(): string
    {
        return self::$debugBar->getJavascriptRenderer()->render();
    }

    /**
     * Init module
     * @return void
     */
    public function init(): void
    {
        if (!isset(self::$debugBar)) {
            self::$debugBar = new StandardDebugBar();
        }

        $this->createAssets();
        $this->default();
    }

    /**
     * Create assets file
     * @return void
     */
    protected function createAssets(): void
    {
        $jsPath = Kernel::$projectPath . '/public/assets/debugbar.js';
        $cssPath = Kernel::$projectPath . '/public/assets/debugbar.css';

        if (!file_exists($jsPath)) {
            ob_start();
            self::$debugBar->getJavascriptRenderer()->dumpJsAssets();
            file_put_contents($jsPath, ob_get_clean());
        }

        if (!file_exists($cssPath)) {
            ob_start();
            self::$debugBar->getJavascriptRenderer()->dumpCssAssets();
            file_put_contents($cssPath, ob_get_clean());
        }
    }

    /**
     * Load default data
     * @return void
     */
    protected function default(): void
    {
        self::$debugBar->addCollector(new ConfigCollector(Config::getAll()));
    }

}