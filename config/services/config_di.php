<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\ConfigEnvFormatterFactory;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetric;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerBuilder $container): void {
    $configVarFormatterFactory = new Definition(ConfigEnvFormatterFactory::class);
    $configVarFormatterFactory->setArgument('$container', $container);
    $container->setDefinition(ConfigEnvFormatterFactory::class, $configVarFormatterFactory);

    $configVarFormatterDef = new Definition(ConfigVariablesFormatter::class);
    $configVarFormatterDef->setFactory(new Reference(ConfigEnvFormatterFactory::class));
    $container->setDefinition(ConfigVariablesFormatter::class, $configVarFormatterDef);

    $configReaderDef = new Definition(ConfigReader::class);
    $configReaderDef->setArgument(
        '$configFile',
        $container->getParameter('preCommitConfigPath'),
    );
    $configReaderDef->setArgument(
        '$configVariablesFormatter',
        new Reference(ConfigVariablesFormatter::class),
    );
    $container->setDefinition(ConfigReader::class, $configReaderDef);

    $configBuilderDef = new Definition(ConfigBuilder::class);
    $configBuilderDef->setArgument('$configReader', new Reference(ConfigReader::class));
    $container->setDefinition(ConfigBuilder::class, $configBuilderDef);

    $timeMetricDef = new Definition(ProcessTimeMetricInterface::class);
    $timeMetricDef->setClass(ProcessTimeMetric::class);
    $timeMetricDef->setArgument('$started', microtime(true));
    $container->setDefinition(ProcessTimeMetricInterface::class, $timeMetricDef);
};
