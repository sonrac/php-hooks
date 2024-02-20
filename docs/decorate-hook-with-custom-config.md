# Create additional custom commands

Create config file in your project with next structure 

```php
<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerBuilder $container): void {
    // define here all what you need in container
};

## Example. Create custom post commit hook with custom config file
<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\CommandDefinition;
use Sonrac\Tools\PhpHook\Commands\HookCommand;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerBuilder $container): void {
    // Define project path
    $container->setParameter('projectDir', __DIR__.'/../../');
    # Define additional env variables template
    $container->setParameter('variables', [
        '{new_var}' => 'some_value'
    ]);

    // Define custom read configuration service and set new config path and variables formatter
    $configReaderDef = new Definition(ConfigReader::class);
    $configReaderDef->setArgument('$configPath', __DIR__.'/pre-commit-hook_1.yaml');
    $configReaderDef->setArgument('$configVariablesFormatter', new Reference(ConfigVariablesFormatter::class));
    
    // Define custom config reader with unique id in container
    $container->setDefinition('custom_config_reader', $configReaderDef);

    // Define custom configuration builder for new hook command
    $configBuilderDef = new Definition(ConfigBuilder::class);
    $configBuilderDef->setArgument('$configReader', new Reference('custom_config_reader'));
    
    // Define custom config builder with unique id in container
    $container->setDefinition('custom_config_builder', $configBuilderDef);

    // Define new command with new instance CommandDefinition - it required definition class for change command name
    $hookCommandDef = new CommandDefinition(HookCommand::class);
    // Change new command name
    $hookCommandDef->setName('test:hook1');
    $hookCommandDef->setArgument('$configBuilder', new Reference('custom_config_builder'));
    $hookCommandDef->setArgument('$processTimeMetric', new Reference(ProcessTimeMetricInterface::class));
    $hookCommandDef->setPublic(true);

    // Command must have this tag
    $hookCommandDef->addTag('console.commands');
    
    // Register new command with unique name in container
    $container->setDefinition('new-hook', $hookCommandDef);
};
```
