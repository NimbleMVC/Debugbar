<?php

namespace NimblePHP\Debugbar\Middleware;

use DebugBar\DataCollector\TemplateCollector;
use DebugBar\DebugBarException;
use Krzysztofzylka\Generator\Generator;
use NimblePHP\Debugbar\Collectors\MiddlewareCollector;
use NimblePHP\Debugbar\Collectors\ModelCollector;
use NimblePHP\Debugbar\Collectors\ModuleCollector;
use NimblePHP\Debugbar\Collectors\ServiceCollector;
use NimblePHP\Debugbar\Debugbar;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\ModelInterface;
use NimblePHP\Framework\Interfaces\ORMModelInterface;
use NimblePHP\Framework\Middleware\Interfaces\ControllerMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ExceptionMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\LogMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ModelMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ORMModelMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ServiceMddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ViewMddlewareInterface;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use Random\RandomException;
use ReflectionMethod;
use Throwable;

class DebugbarMiddleware implements ControllerMiddlewareInterface, ViewMddlewareInterface, LogMiddlewareInterface, ModelMiddlewareInterface, ExceptionMiddlewareInterface, ORMModelMiddlewareInterface, ServiceMddlewareInterface
{

    /**
     * @return void
     * @throws NimbleException
     */
    public function afterBootstrap(): void
    {
        if (!$_ENV['DEBUG']) {
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
        try {
            if ($_ENV['DEBUG'] && (new Request())->getUri() === '/.well-known/appspecific/com.chrome.devtools.json') {
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
        try {
            (new Debugbar())->init();

            if (Debugbar::$init) {
                if (Config::get('DEBUG_HIDE_CONFIG', false) && Debugbar::$debugBar->hasCollector('config')) {
                    Debugbar::$debugBar['config']->setData([]);
                }
            }

            if (!Debugbar::$debugBar->hasCollector('templates')) {
                Debugbar::$debugBar->addCollector(new TemplateCollector());
            }
        } catch (DebugBarException $exception) {
            throw new NimbleException($exception->getMessage(), 500);
        }
    }

    /**
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     * @return void
     * @throws NimbleException
     */
    public function afterController(string $controllerName, string $methodName, array $params): void
    {
        if (!Config::get('DEBUG', false)) {
            return;
        }

        try {
            if (!Debugbar::$debugBar->hasCollector('services')) {
                Debugbar::$debugBar->addCollector(new ServiceCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('middlewares')) {
                Debugbar::$debugBar->addCollector(new MiddlewareCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('modules')) {
                Debugbar::$debugBar->addCollector(new ModuleCollector());
            }

            if (!Debugbar::$debugBar->hasCollector('models')) {
                Debugbar::$debugBar->addCollector(new ModelCollector());
            }
        } catch (Throwable $th) {
            throw new NimbleException($th->getMessage(), 500);
        }
    }

    /**
     * @param array $controllerContext
     * @return void
     */
    public function beforeController(array &$controllerContext): void
    {
    }

    /**
     * @param ReflectionMethod $reflection
     * @param object $controller
     * @return void
     */
    public function afterAttributesController(ReflectionMethod $reflection, object $controller): void
    {
    }

    /**
     * @param array $data
     * @return void
     */
    public function processingViewData(array &$data): void
    {
    }

    /**
     * @param array $data
     * @param string $viewName
     * @param string $filePath
     * @return void
     */
    public function afterviewRender(array $data, string $viewName, string $filePath): void
    {
        $realpath = realpath($filePath);
        /** @var TemplateCollector $templates */
        $templates = Debugbar::$debugBar['templates'];

        $templates->addTemplate(
            empty($realpath) ? $filePath : $realpath,
            $data,
            pathinfo($filePath, PATHINFO_EXTENSION),
            md5(json_encode($data) . $viewName, $filePath)
        );
    }

    /**
     * @param array $data
     * @param string $viewName
     * @param string $filePath
     * @return void
     */
    public function beforeViewRender(array $data, string $viewName, string $filePath): void
    {
    }

    /**
     * @param string $message
     * @return void
     */
    public function beforeLog(string &$message): void
    {
    }

    /**
     * @param array $logContent
     * @return void
     */
    public function afterLog(array &$logContent): void
    {
        Debugbar::addMessage('Log: ' . $logContent['message'], 'log', $logContent);
    }

    public function afterConstructModel(ModelInterface $model): void
    {
        Debugbar::increaseModelData($model::class);
    }

    /**
     * @param array $data
     * @return void
     */
    public function processingModelData(array &$data): void
    {
    }

    /**
     * @param array $data
     * @return void
     */
    public function processingModelQuery(array &$data): void
    {
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHook(\Throwable $exception): void
    {
        Debugbar::addException($exception);
    }

    /**
     * @param ORMModelInterface $model
     * @return void
     */
    public function afterConstructORMModel(ORMModelInterface $model): void
    {
        Debugbar::increaseModelData($model::class);
    }

    /**
     * @param string $id
     * @return void
     */
    public function serviceGet(string $id): void
    {
        Debugbar::increaseServiceData($id, 'get');
    }

    /**
     * @param string $id
     * @return void
     */
    public function serviceHas(string $id): void
    {
        Debugbar::increaseServiceData($id, 'has');
    }

    /**
     * @param string $id
     * @return void
     */
    public function serviceRemove(string $id): void
    {
        Debugbar::increaseServiceData($id, 'remove');
    }

    /**
     * @param string $id
     * @param mixed $service
     * @return void
     */
    public function serviceSet(string $id, mixed $service): void
    {
        Debugbar::increaseServiceData($id, 'set');
    }

}