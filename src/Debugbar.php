<?php

namespace NimblePHP\Debugbar;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\OpenHandler;
use DebugBar\StandardDebugBar;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use Krzysztofzylka\File\File;
use NimblePHP\Debugbar\Collectors\PhpConfigCollector;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Request;
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
            self::$init = false;
            self::$debugBar = new StandardDebugBar();
            self::$javascriptRenderer = self::$debugBar->getJavascriptRenderer();
            self::$javascriptRenderer->setOpenHandlerUrl('/debugbar/open');
        }

        if ($_ENV['DEBUG'] ?? false) {
            self::$init = true;
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

            $uri = (new Request())->getUri();

            if (str_starts_with($uri, '/debugbar/open')) {
                $openHandler = new OpenHandler(self::$debugBar);
                $openHandler->handle();
                exit;
            } elseif (str_starts_with($uri, '/vendor/php-debugbar/php-debugbar/')) {
                $response = new Response();
                $response->addHeader('Cache-Control', 'public, max-age=3600');

                $fileExtension = pathinfo($uri, PATHINFO_EXTENSION);
                switch ($fileExtension) {
                    case 'css':
                        $response->addHeader('Content-Type', 'text/css');
                        break;
                    case 'js':
                        $response->addHeader('Content-Type', 'application/javascript');
                        break;
                    case 'png':
                        $response->addHeader('Content-Type', 'image/png');
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $response->addHeader('Content-Type', 'image/jpeg');
                        break;
                    case 'gif':
                        $response->addHeader('Content-Type', 'image/gif');
                        break;
                    case 'svg':
                        $response->addHeader('Content-Type', 'image/svg+xml');
                        break;
                    case 'woff':
                        $response->addHeader('Content-Type', 'font/woff');
                        break;
                    case 'woff2':
                        $response->addHeader('Content-Type', 'font/woff2');
                        break;
                    case 'ttf':
                        $response->addHeader('Content-Type', 'font/ttf');
                        break;
                    case 'eot':
                        $response->addHeader('Content-Type', 'application/vnd.ms-fontobject');
                        break;
                    default:
                        $response->addHeader('Content-Type', 'text/plain');
                        break;
                }

                $filePath = Kernel::$projectPath . $uri;
                if (file_exists($filePath)) {
                    $response->setContent(file_get_contents($filePath));
                } else {
                    $response->setStatusCode(404);
                    $response->setContent('File not found');
                }

                $response->send();
                exit;
            }
        }

        if (!self::$debugBar->hasCollector('config')) {
            ksort($_ENV);
            self::$debugBar->addCollector(new ConfigCollector($_ENV));
        }

        if ($_ENV['DATABASE'] && !self::$debugBar->hasCollector('pdo')) {
            $pdoCollector = new PDOCollector(DatabaseManager::$connection->getConnection());
            $pdoCollector->enableBacktrace(20);
            self::$debugBar->addCollector($pdoCollector);
        }

        if (!self::$debugBar->hasCollector('php_config')) {
            self::$debugBar->addCollector(new PhpConfigCollector());
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