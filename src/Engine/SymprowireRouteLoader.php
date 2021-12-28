<?php

namespace Symprowire\Engine;

use JetBrains\PhpStorm\Pure;
use ProcessWire\ProcessWire;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SymprowireRouteLoader extends Loader
{
    protected ProcessWire $wire;

    #[Pure]
    public function __construct(ProcessWire $processWire) {
        $this->wire = $processWire;
        parent::__construct();
    }

    /**
     *
     * Load all ProcessWire Templates as own route
     * add a Route if _sympro is set
     *
     * @param mixed $resource
     * @param string|null $type
     * @return RouteCollection
     */
    public function load(mixed $resource, string $type = null): RouteCollection
    {
        $routes = new RouteCollection();
        $templates = $this->wire->templates->find('altFilename=controller');

        foreach($templates as $template) {
            $path = '/'. $this->wire->sanitizer->pageName($template->name);
            $controllerName = $this->wire->sanitizer->camelCase($template->name);
            $controllerName = ucfirst($controllerName);
            $defaults = ['_controller' => $this->buildControllerName($template->name) . '::index'];
            $requirements = [];
            $route = new Route($path, $defaults, $requirements);
            $routeName = strtolower($controllerName) . '_index';
            $routes->add($routeName, $route);
        }

        return $routes;
    }

    protected function buildControllerName(string $templateName, string $namespace = ''): string {

        $controllerName = $this->wire->sanitizer->camelCase($templateName);
        if(!$namespace) {
            $namespace = '\\Symprowire\\Controller\\';
        }

        return $namespace.ucfirst($controllerName) . 'Controller';
    }

    public function supports($resource, string $type = null): bool
    {
        return 'processwire' === $type;
    }
}
