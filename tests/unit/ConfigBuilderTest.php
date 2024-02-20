<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;

final class ConfigBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $configBuilder = new ConfigBuilder(
            new ConfigReader(
                __DIR__ . '/../../config/pre-commit-hook.yaml',
                new ConfigVariablesFormatter(),
            ),
        );

        $dto = $configBuilder->build();
        self::assertEquals('Pre-commit hook', $dto->getName());
        self::assertCount(5, $dto->getCommands());
    }
}
