<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {

    $root = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
    $site = $root.'site'.DIRECTORY_SEPARATOR;

    $container->parameters()->set('app.paths.root', $root);
    $container->parameters()->set('app.paths.site', $site);
    $container->parameters()->set('app.paths.modules', $site.'modules'.DIRECTORY_SEPARATOR);
    $container->parameters()->set('app.paths.assets', $site.'assets'.DIRECTORY_SEPARATOR);
    $container->parameters()->set('app.paths.templates', $site.'templates'.DIRECTORY_SEPARATOR);

};
