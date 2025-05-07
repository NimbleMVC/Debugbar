# <h1 align="center">NimblePHP - Migrations</h1>
Debugbar jest modułem który umożliwia w łatwy sposób zintegrować PHP Debugbar do projektu opartego na NimblePHP

**Dokumentacja** projektu dostępna jest pod linkiem:
https://nimblemvc.github.io/documentation/extension/debugbar/start/#

## Instalacja
```shell
composer require nimblephp/debugbar
```

## Użycie
Po zainstalowaniu composera tworzymy lub edytujemy plik `Middleware.php` i wklejamy w metodę `afterBootstrap` następujący kod:
```php
(new Debugbar())->init();
```
Dodajemy w szablonie:
```php
echo \NimblePHP\Debugbar\Debugbar::renderHeader();
```
Teraz zainicjował się debugbar i możemy go ustawić w `index.php` na końcu
```php
echo \NimblePHP\debugbar\Debugbar::render();
```

### Gotowy plik `Middleware.php` z wyliczaniem czasu ładowania kontrolera oraz zwróceniem błędów
```php
<?php

use NimblePHP\debugbar\Debugbar;

class Middleware extends \NimblePHP\Framework\Middleware
{

    public function afterBootstrap()
    {
        (new Debugbar())->init();
    }

    public function handleException(Throwable $exception)
    {
        \NimblePHP\Debugbar\Debugbar::$debugBar['exceptions']->addThrowable($exception);
    }

    public function beforeController(string $controllerName, string $action, array $params)
    {
        \NimblePHP\Debugbar\Debugbar::$debugBar['time']->startMeasure('load-controller-' . $controllerName . $action, 'Load ' . str_replace('\src\Controller\\', '', $controllerName) . ' controller');
    }

    public function afterController(string $controllerName, string $action, array $params)
    {
        \NimblePHP\Debugbar\Debugbar::$debugBar['time']->stopMeasure('load-controller-' . $controllerName . $action);
        
        if (!Debugbar::$debugBar->hasCollector('module_register')) {
            Debugbar::$debugBar->addCollector(new \NimblePHP\Debugbar\Collectors\ModuleCollector(\NimblePHP\Framework\ModuleRegister::getAll()));
        }
    }

}
```

## Współtworzenie
Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błędy, chcesz pomóc w rozwoju? Otwórz issue lub prześlij pull request.

## Pomoc
Wszelkie problemy oraz pytania należy zadawać przez zakładkę discussions w github pod linkiem:
https://github.com/NimbleMVC/Migrations/discussions
