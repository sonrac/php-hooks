<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerBuilder $container): void {
    $container->setParameter(
        'projectDir',
        dirname(__DIR__, 2),
    );

    $container->setParameter(
        'preCommitConfigPath',
        dirname(__DIR__) . '/hook.yaml',
    );

    $container->setParameter('templateVariables', []);
};
