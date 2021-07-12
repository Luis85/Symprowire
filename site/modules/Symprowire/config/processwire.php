<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use function ProcessWire\wire;

return static function (ContainerConfigurator $container) {

    $config = wire('config');
    $paths = $config->paths;
    $container->parameters()->set('app.paths.root', $paths->root);
    $container->parameters()->set('app.paths.site', $paths->site);
    $container->parameters()->set('app.paths.modules', $paths->modules);
    $container->parameters()->set('app.paths.assets', $paths->assets);
    $container->parameters()->set('app.paths.templates', $paths->templates.'twig');
    $container->parameters()->set('app.debug', $config->debug);
    $container->parameters()->set('app.advanced', $config->advanced);

};
