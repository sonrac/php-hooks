<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerBuilder $container): void {
    $definition = new Definition(ConfigReader::class);

    $definition->setArgument(
        '$configPath',
        $container->getParameter('preCommitConfigPath'),
    );

    $definition->setArgument(
        '$configVariablesFormatter',
        new Reference(ConfigVariablesFormatter::class),
    );

    $container->setDefinition(ConfigReader::class, $definition);
};
