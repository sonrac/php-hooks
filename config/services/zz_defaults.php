<?php

declare(strict_types=1);

use _PHPStan_156ee64ba\Nette\DI\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

return static function(ContainerBuilder $container): void
{
    $definition = new Definition();
    $definition->setPublic(true);
    $definition->setAutowired(true);
    $definition->setAutoconfigured(true);
    $container->registerClasses($definition, 'Sonrac\Tools\PhpHook\\', __DIR__ . '/../src/*');
}
