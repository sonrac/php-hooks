<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;

final class ConfigReaderTest extends TestCase
{
    private ConfigReader $configReader;

    public function testReadConfig(): void
    {
        $config = $this->configReader->readMainSection();
        self::assertArrayHasKey(ConfigReader::COMMANDS, $config);
        self::assertArrayHasKey(ConfigReader::DESCRIPTION, $config);
        self::assertArrayHasKey(ConfigReader::NAME, $config);
        self::assertArrayHasKey(ConfigReader::GLOBAL_ENV, $config);
        self::assertArrayHasKey(ConfigReader::GLOBAL_ENV_FILE, $config);
    }

    public function testReadCommands(): void
    {
        $config = $this->configReader->readCommands();
        self::assertCount(5, $config);
    }

    public function testExceptionWhenFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $configReader = new ConfigReader(
            __DIR__ . '/../../config/_pre-commit-hook.yaml',
            new ConfigVariablesFormatter(),
        );
    }

    public function testEmptyCommands(): void
    {
        $configReader = new ConfigReader(
            __DIR__ . '/../stub/empty_commands.yaml',
            new ConfigVariablesFormatter(),
        );
        $commands = $configReader->readCommands();
        self::assertEmpty($commands);

        $configReader = new ConfigReader(
            __DIR__ . '/../stub/null_commands.yaml',
            new ConfigVariablesFormatter(),
        );

        $commands = $configReader->readCommands();
        self::assertEmpty($commands);
    }

    public function testInvalidCommandsConfiguration(): void
    {
        $configReader = new ConfigReader(
            __DIR__ . '/../stub/invalid_commands.yaml',
            new ConfigVariablesFormatter(),
        );
        $this->expectException(RuntimeException::class);
        $configReader->readCommands();
    }

    public function testInvalidCommandsArgsConfiguration(): void
    {
        $configReader = new ConfigReader(
            __DIR__ . '/../stub/invalid_command_definition.yaml',
            new ConfigVariablesFormatter(),
        );
        $this->expectException(RuntimeException::class);
        $configReader->readCommands();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configReader = new ConfigReader(
            __DIR__ . '/../../config/hook.yaml',
            new ConfigVariablesFormatter(),
        );
    }
}
