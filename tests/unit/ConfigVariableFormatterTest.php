<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;

final class ConfigVariableFormatterTest extends TestCase
{
    public function testFormatVariables(): void
    {
        $configFormatter = new ConfigVariablesFormatter('/app');

        // @phpstan-ignore-next-line
        $formatted = $configFormatter->format([
            'test' => '{composer_cmd}',
            'merge' => [
                'data' => '%php_cmd',
                'data1' => '{php_cmd}',
                'int' => 0,
                'dir' => '{project_dir}'
            ],
        ]);

        self::assertArrayHasKey('test', $formatted);
        self::assertEquals('/usr/local/bin/composer', $formatted['test']);
        self::assertArrayHasKey('merge', $formatted);
        // @phpstan-ignore-next-line
        self::assertArrayHasKey('data', $formatted['merge']);
        // @phpstan-ignore-next-line
        self::assertEquals('%php_cmd', $formatted['merge']['data']);
        try {
            // @phpstan-ignore-next-line
            self::assertEquals('/usr/local/bin/php', $formatted['merge']['data1']);
        } catch (\Throwable $t) {
            // @phpstan-ignore-next-line
            self::assertEquals('/usr/bin/php', $formatted['merge']['data1']);
        }
        // @phpstan-ignore-next-line
        self::assertEquals(0, $formatted['merge']['int']);
        // @phpstan-ignore-next-line
        self::assertEquals('/app', $formatted['merge']['dir']);
    }

    /**
     * @throws ReflectionException
     */
    public function testRunCmd(): void
    {
        $configFormatter = new ConfigVariablesFormatter('/app');

        $reflectionClass = new \ReflectionClass($configFormatter);
        $method = $reflectionClass->getMethod('findCommandLocation');
        $method->setAccessible(true);

        self::assertEquals('/usr/local/bin/composer', $method->invoke($configFormatter, 'composer'));
        self::assertEquals('', $method->invoke($configFormatter, 'not_existed_cmd'));
    }
}
