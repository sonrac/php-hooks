<?php

declare(strict_types=1);

use Sonrac\Tools\PhpHook\CommandDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

return static function (): ContainerBuilder {
    $container = new ContainerBuilder();

    (require __DIR__ . '/services/parameters.php')($container);
    (require __DIR__ . '/services/zz_defaults.php')($container);

    return $container;
};
