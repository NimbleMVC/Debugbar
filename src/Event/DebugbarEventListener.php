<?php

namespace NimblePHP\Debugbar\Event;

use DebugBar\DebugBarException;
use Krzysztofzylka\Generator\Generator;
use NimblePHP\Debugbar\Collectors\EventCollector;
use NimblePHP\Debugbar\Collectors\MiddlewareCollector;
use NimblePHP\Debugbar\Collectors\ModelCollector;
use NimblePHP\Debugbar\Collectors\ModuleCollector;
use NimblePHP\Debugbar\Collectors\ServiceCollector;
use NimblePHP\Debugbar\Collectors\TemplateCollector;
use NimblePHP\Debugbar\Collectors\TranslationCollector;
use NimblePHP\Debugbar\Debugbar;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Response;
use Random\RandomException;
use Throwable;

class DebugbarEventListener
{
    private const EVENT_PRIORITY = -100000;

    public static function register(): bool
    {
        if (!method_exists(Kernel::class, 'getEventDispatcher')) {
            return false;
        }

        $dispatcher = Kernel::getEventDispatcher();

        if (!is_object($dispatcher) || !method_exists($dispatcher, 'addListener')) {
            return false;
        }

        $listener = new self();
        $registered = false;
        $register = function (string $eventClass, callable $handler) use ($dispatcher, &$registered): void {
            if (!class_exists($eventClass)) {
                return;
            }

            $dispatcher->addListener($eventClass, $handler, self::EVENT_PRIORITY);
            $registered = true;
        };

        $register('NimblePHP\Framework\Event\Framework\AfterBootstrapEvent', function () use ($listener): void {
            $listener->afterBootstrap();
        });
        $register('NimblePHP\Framework\Event\Framework\AfterControllerEvent', function () use ($listener): void {
            $listener->afterController();
        });
        $register('NimblePHP\Framework\Event\Framework\AfterViewRenderEvent', function (object $event) use ($listener): void {
            $listener->afterViewRender(
                self::getArrayProperty($event, ['previewData', 'data']),
                self::getStringProperty($event, ['viewName']),
                self::getStringProperty($event, ['filePath'])
            );
        });
        $register('NimblePHP\Framework\Event\Framework\AfterLogEvent', function (object $event) use ($listener): void {
            $listener->afterLog(self::getArrayProperty($event, ['payload', 'logContent']));
        });
        $register('NimblePHP\Framework\Event\Framework\AfterConstructModelEvent', function (object $event) use ($listener): void {
            $listener->afterConstructModel($event->model ?? null);
        });
        $register('NimblePHP\Framework\Event\Framework\AfterConstructOrmModelEvent', function (object $event) use ($listener): void {
            $listener->afterConstructModel($event->model ?? null);
        });
        $register('NimblePHP\Framework\Event\Framework\AfterConstructORMModelEvent', function (object $event) use ($listener): void {
            $listener->afterConstructModel($event->model ?? null);
        });
        $register('NimblePHP\Framework\Event\Framework\ExceptionEvent', function (object $event) use ($listener): void {
            $exception = $event->exception ?? null;

            if ($exception instanceof Throwable) {
                $listener->exceptionHook($exception);
            }
        });

        return $registered;
    }

    /**
     * @return void
     * @throws NimbleException
     */
    public function afterBootstrap(): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        $this->chromeDebug();
        $this->initDebugbar();
    }

    /**
     * @return void
     * @throws NimbleException
     */
    private function chromeDebug(): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        try {
            if (Kernel::$serviceContainer->get('kernel.request')->getUri() === '/.well-known/appspecific/com.chrome.devtools.json') {
                $response = new Response();
                $response->setJsonContent([
                    'workspace' => [
                        'root' => str_replace('/public', '', $_ENV['WEBROOT_PATH']),
                        'uuid' => Generator::uuid()
                    ]
                ]);
                $response->send(true);
            }
        } catch (RandomException $exception) {
            throw new NimbleException($exception->getMessage(), 500);
        }
    }

    /**
     * @return void
     * @throws NimbleException
     */
    private function initDebugbar(): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        try {
            (new Debugbar())->init();

            if (!Debugbar::$debugBar->hasCollector('templates')) {
                Debugbar::$debugBar->addCollector(new TemplateCollector());
            }
        } catch (DebugBarException $exception) {
            throw new NimbleException($exception->getMessage(), 500);
        }
    }

    /**
     * @return void
     * @throws NimbleException
     */
    public function afterController(): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        try {
            if (!Debugbar::$debugBar->hasCollector('services')) {
                Debugbar::$debugBar->addCollector(new ServiceCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('middlewares') && count(MiddlewareCollector::getMiddlewares()) > 0) {
                Debugbar::$debugBar->addCollector(new MiddlewareCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('events') && count(EventCollector::getEvents()) > 0) {
                Debugbar::$debugBar->addCollector(new EventCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('modules')) {
                Debugbar::$debugBar->addCollector(new ModuleCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('models')) {
                Debugbar::$debugBar->addCollector(new ModelCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('translation')) {
                Debugbar::$debugBar->addCollector(new TranslationCollector());
            }
        } catch (Throwable $throwable) {
            throw new NimbleException($throwable->getMessage(), 500);
        }
    }

    /**
     * @param array $data
     * @param string $viewName
     * @param string $filePath
     * @return void
     */
    public function afterViewRender(array $data, string $viewName, string $filePath): void
    {
        if (!Config::get('DEBUG', false) || $filePath === '' || !Debugbar::$debugBar->hasCollector('templates')) {
            return;
        }

        $realpath = realpath($filePath);
        /** @var TemplateCollector $templates */
        $templates = Debugbar::$debugBar['templates'];
        $encodedData = json_encode($data);

        $templates->addTemplate(
            empty($realpath) ? $filePath : $realpath,
            $data,
            pathinfo($filePath, PATHINFO_EXTENSION),
            md5(($encodedData ?: '') . $viewName)
        );
    }

    /**
     * @param array $logContent
     * @return void
     */
    public function afterLog(array $logContent): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        Debugbar::addMessage('Log: ' . ($logContent['message'] ?? ''), 'log', $logContent);
    }

    /**
     * @param mixed $model
     * @return void
     */
    public function afterConstructModel(mixed $model): void
    {
        if (!Config::get('DEBUG', false) || !is_object($model)) {
            return;
        }

        Debugbar::increaseModelData($model::class);
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHook(Throwable $exception): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        Debugbar::addException($exception);
    }

    /**
     * @param object $event
     * @param array $keys
     * @return array
     */
    private static function getArrayProperty(object $event, array $keys): array
    {
        foreach ($keys as $key) {
            if (!property_exists($event, $key) || !is_array($event->{$key})) {
                continue;
            }

            return $event->{$key};
        }

        return [];
    }

    /**
     * @param object $event
     * @param array $keys
     * @return string
     */
    private static function getStringProperty(object $event, array $keys): string
    {
        foreach ($keys as $key) {
            if (!property_exists($event, $key) || !is_scalar($event->{$key})) {
                continue;
            }

            return (string)$event->{$key};
        }

        return '';
    }
}
