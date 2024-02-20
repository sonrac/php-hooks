<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonrac\Tools\PhpHook\ConfigEnvFormatterFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ConfigEnvFormatterFactoryTest extends TestCase
{
    public function testCreateWithEmptyTemplateVariables(): void
    {
        $container = new Container();
        $container->setParameter('templateVariables', null);
        $container->setParameter('projectDir', __DIR__);
        $container->compile();
        $factory = new ConfigEnvFormatterFactory($container);

        $formatter = $factory->__invoke();
        self::assertTrue(is_array($formatter->format([])));
    }
}
