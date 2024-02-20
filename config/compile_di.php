<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\CommandDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @return Command[] */
return static function (): array {
    $di = require __DIR__ . '/../config/di.php';

    /** @var ContainerBuilder $container */
    $container = $di();

    $containerFile = $_SERVER['HOOK_DI_FILE'] ?? null;

    if (is_string($containerFile) && is_file($containerFile)) {
        (require $containerFile)($container);
    }

    if (0 !== count($container->findTaggedServiceIds('console.commands'))) {
        $services = $container->findTaggedServiceIds('console.commands');

        foreach ($services as $class => $service) {
            $definition = $container->getDefinition($class);
            if ($definition instanceof CommandDefinition) {
                $definition->addMethodCall('setName', [$definition->getName()]);
            }
        }
    }

    $container->compile();

    $commands = [];
    $services = $container->findTaggedServiceIds('console.commands');

    foreach ($services as $service => $config) {
        $commands[] = $container->get($service);
    }

    return $commands;
};
