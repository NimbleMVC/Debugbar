<?php

namespace Nimblephp\debugbar;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use Nimblephp\framework\Config;
use Nimblephp\framework\Kernel;
use Nimblephp\framework\Response;

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
     * Javascript renderer instance
     * @var JavascriptRenderer
     */
    public static JavascriptRenderer $javascriptRenderer;

    /**
     * Init module
     * @return void
     * @throws DebugBarException
     */
    public static function init(): void
    {
        if (!isset(self::$debugBar)) {
            self::$debugBar = new StandardDebugBar();
            self::$javascriptRenderer = self::$debugBar->getJavascriptRenderer();
        }

        if (Config::get('DEBUG', false)) {
            $uri = $_SERVER['REQUEST_URI'];

            if (str_starts_with($uri, '/vendor/maximebf/debugbar/')) {
                $response = new Response();
                $response->setContent(file_get_contents(Kernel::$projectPath . $uri));
                $response->send();
                exit;
            }
        }

        self::$debugBar->addCollector(new ConfigCollector(Config::getAll()));
        self::$debugBar->addCollector(new PDOCollector(DatabaseManager::$connection->getConnection()));
    }

    /**
     * Render debugbar
     * @return string
     */
    public static function render(): string
    {
        return self::$javascriptRenderer->render();
    }

    /**
     * Render header
     * @return string
     */
    public static function renderHeader(): string
    {
        return self::$javascriptRenderer->renderHead();
    }

}