<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerInterface;

return static function(ContainerInterface  $container): void
{
    $container->setParameter('project_dir', __DIR__.'/../');
};
