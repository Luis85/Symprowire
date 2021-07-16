<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {

    $root = $_SERVER['DOCUMENT_ROOT'];
    $site = $root.'site';

    $container->parameters()->set('app.paths.root', $root);
    $container->parameters()->set('app.paths.site', $site);
    $container->parameters()->set('app.paths.modules', $site.'/modules/');
    $container->parameters()->set('app.paths.assets', $site.'/assets/');
    $container->parameters()->set('app.paths.templates', $site.'/templates/');

};
