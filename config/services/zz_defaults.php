<?php

declare(strict_types=1);

use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

return static function (ContainerBuilder $container): void {
    $definition = new Definition();
    $definition->setPublic(true);
    $definition->setAutowired(true);
    $definition->setAutoconfigured(true);

    $loader = new PhpFileLoader(
        $container,
        new FileLocator(__DIR__),
        null,
    );

    $loader->load('parameters.php');
    $loader->load('config_di.php');
    $loader->load('command.php');
};
