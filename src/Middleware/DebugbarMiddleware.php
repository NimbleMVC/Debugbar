<?php

namespace NimblePHP\Debugbar\Middleware;

use DebugBar\DebugBarException;
use Krzysztofzylka\Generator\Generator;
use NimblePHP\Debugbar\Collectors\ServiceCollector;
use NimblePHP\Debugbar\Debugbar;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Middleware\Interfaces\ControllerMiddlewareInterface;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use Random\RandomException;
use Throwable;

class DebugbarMiddleware implements ControllerMiddlewareInterface
{

    /**
     * @return void
     * @throws DebugBarException
     * @throws NimbleException
     */
    public function afterBootstrap(): void
    {
        (new Debugbar())->init();

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
                if ($_ENV['DEBUG_HIDE_CONFIG'] && Debugbar::$debugBar->hasCollector('config')) {
                    Debugbar::$debugBar['config']->setData([]);
                }
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
        if (!$_ENV['DEBUG']) {
            return;
        }

        try {
            if (!Debugbar::$debugBar->hasCollector('services')) {
                Debugbar::$debugBar->addCollector(new ServiceCollector());
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

}