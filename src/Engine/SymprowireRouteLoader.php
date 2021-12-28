<?php

namespace Symprowire\Engine;

use JetBrains\PhpStorm\Pure;
use ProcessWire\ProcessWire;
use ProcessWire\Wire;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SymprowireRouteLoader extends Loader
{
    protected ?Wire $wire;

    #[Pure]
    /**
     * we typehint Wire, this will accept our ProcessWire Mock
     * To check if its the real ProcessWire instance installed and running we have to check for ProcessWire explicitly
     * We do not want to set the Mock to $this as routing is created from the Database
     */
    public function __construct(Wire $processWire) {
        if($processWire instanceof ProcessWire) {
            $this->wire = $processWire;
        } else {
            $this->wire = null;
        }
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

        /**
         * Return an empty RouteCollection if ProcessWire is not present
         * This will open up the whole Setup for the Console as we now could call the codebase without ProcessWire
         */
        if(!$this->wire) return $routes;

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
