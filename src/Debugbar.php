<?php

namespace NimblePHP\Debugbar;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\OpenHandler;
use DebugBar\StandardDebugBar;
use Krzysztofzylka\File\File;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Response;
use Throwable;

/**
 * Debugbar module
 */
class Debugbar
{

    /**
     * Is init
     * @var bool
     */
    public static bool $init = false;

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
            self::$init = true;
            self::$debugBar = new StandardDebugBar();
            self::$javascriptRenderer = self::$debugBar->getJavascriptRenderer();
            self::$javascriptRenderer->setOpenHandlerUrl('/debugbar/open');
        }

        if ($_ENV['DEBUG'] ?? false) {
            $storagePath = Kernel::$projectPath . '/storage/debugbar';

            if ($_ENV['DEBUGBAR_STORAGE'] ?? false) {
                File::mkdir($storagePath);
                self::$debugBar->setStorage(new FileStorage($storagePath));
            } elseif (file_exists($storagePath)) {
                $deleted = 0;
                $files = glob($storagePath . '/*');

                foreach ($files as $file) {
                    unlink($file);
                    $deleted++;
                }

                if ($deleted > 0) {
                    Debugbar::addMessage('Delete ' . $deleted . ' old debugbar cache files', 'Debugbar');
                }
            }

            $uri = $_SERVER['REQUEST_URI'];

            if (str_starts_with($uri, '/debugbar/open')) {
                $openHandler = new OpenHandler(self::$debugBar);
                $openHandler->handle();
                exit;
            } elseif (str_starts_with($uri, '/vendor/php-debugbar/php-debugbar/')) {
                $response = new Response();
                $response->addHeader('Cache-Control', 'public, max-age=3600');
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
        if (!self::$init) {
            return '';
        }

        return self::$javascriptRenderer->render();
    }

    /**
     * Render header
     * @return string
     */
    public static function renderHeader(): string
    {
        if (!self::$init) {
            return '';
        }

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
        if (!self::$init) {
            return;
        }

        self::$debugBar['messages']->addMessage($data, $label);
    }

    /**
     * Add exception
     * @param Throwable $exception
     * @return void
     */
    public static function addException(Throwable $exception): void
    {
        if (!self::$init) {
            return;
        }

        self::$debugBar['exceptions']->addThrowable($exception);
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
        if (!self::$init) {
            return;
        }

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
        if (!self::$init) {
            return;
        }

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
        } catch (Throwable $e) {
            throw new NimbleException('Failed generate uuid: ' . $e->getMessage(), 500);
        }
    }

}