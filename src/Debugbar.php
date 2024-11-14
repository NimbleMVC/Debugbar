<?php

namespace Nimblephp\debugbar;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\OpenHandler;
use DebugBar\StandardDebugBar;
use DebugBar\Storage\FileStorage;
use Exception;
use Krzysztofzylka\File\File;
use Nimblephp\framework\Exception\NimbleException;
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
            self::$javascriptRenderer->setOpenHandlerUrl('/debugbar/open');
        }

        if ($_ENV['DEBUG'] ?? false) {
            $storagePath = Kernel::$projectPath . '/storage/debugbar';
            File::mkdir($storagePath);
            self::$debugBar->setStorage(new FileStorage($storagePath));

            $uri = $_SERVER['REQUEST_URI'];

            if (str_starts_with($uri, '/debugbar/open')) {
                $openHandler = new OpenHandler(self::$debugBar);
                $openHandler->handle();
                exit;
            } elseif (str_starts_with($uri, '/vendor/maximebf/debugbar/')) {
                $response = new Response();
                $response->setContent(file_get_contents(Kernel::$projectPath . $uri));
                $response->send();
                exit;
            }
        }

        if (!self::$debugBar->hasCollector('config')) {
            self::$debugBar->addCollector(new ConfigCollector($_ENV));
        }
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

    /**
     * Add message
     * @param mixed $data
     * @param string $label
     * @return void
     */
    public static function addMessage(mixed $data, string $label = 'info'): void
    {
        self::$debugBar['messages']->addMessage($data, $label);
    }

    /**
     * Add exception
     * @param Exception $exception
     * @return void
     */
    public static function addException(Exception $exception): void
    {
        self::$debugBar['exceptions']->addException($exception);
    }

    /**
     * Start time
     * @param $name
     * @param $label
     * @param $collector
     * @return void
     */
    public static function startTime($name, $label = null, $collector = null): void
    {
        self::$debugBar['time']->startMeasure($name, $label, $collector);
    }

    /**
     * Stop time
     * @param $name
     * @param $params
     * @return void
     */
    public static function stopTime($name, $params = array()): void
    {
        self::$debugBar['time']->stopMeasure($name, $params);
    }

    /**
     * Render uuid
     * @param null $data
     * @return string
     * @throws NimbleException
     */
    public static function uuid($data = null): string
    {
        try {
            $data = $data ?? random_bytes(16);
            assert(strlen($data) == 16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } catch (\Throwable $e) {
            throw new NimbleException('Failed generate uuid: ' . $e->getMessage(), 500);
        }
    }

}