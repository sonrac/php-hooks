<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\Commands\HookCommand;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerBuilder $container): void {
    $hookCommandDef = new Definition(HookCommand::class);
    $hookCommandDef->setArgument('$configBuilder', new Reference(ConfigBuilder::class));
    $hookCommandDef->setArgument('$processTimeMetric', new Reference(ProcessTimeMetricInterface::class));
    $hookCommandDef->setPublic(true);
    $hookCommandDef->addTag('console.commands');

    $container->setDefinition(HookCommand::class, $hookCommandDef)
        ->addTag('console.commands');
};
