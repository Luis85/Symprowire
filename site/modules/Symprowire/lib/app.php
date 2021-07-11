<?php

/*
 * Base Application router
 * Implements Symfony/routing via PHP Arrays
 * https://symfony.com/doc/current/routing.html#creating-routes
 */
use Symfony\Component\Routing;

$routes = new Routing\RouteCollection();

$routes->add('home', new Routing\Route('/', [
    '_controller' => 'App\Controller\HomeController::index',
]));

return $routes;
