<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonrac\Tools\PhpHook\Commands\HookCommand;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigReader;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetric;
use Symfony\Component\Console\Tester\CommandTester;

final class HookCommandTest extends TestCase
{
    private CommandTester $commandTester;

    public function testSuccessWithEmptyFiles(): void
    {
        self::assertEquals(0, $this->commandTester->execute([]));
    }

    public function testRunWithErrors(): void
    {
        self::assertEquals(1, $this->commandTester->execute([
            'files' => [__DIR__ . '/../../example/invalid_style.php'],
        ]));
    }

    public function testRunSuccessfully(): void
    {
        self::assertEquals(
            0,
            $this->commandTester->execute(
                [
                    'files' => [__DIR__ . '/../../src/ConfigEnvFormatterFactory.php'],
                ],
                [
                    'project-dir' => __DIR__ . '/../../',
                    'config' => __DIR__ . '/../../config/pre-commit-hook-test.yaml',
                ],
            ),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandTester = new CommandTester(
            new HookCommand(
                new ConfigBuilder(
                    new ConfigReader(
                        __DIR__ . '/../../config/pre-commit-hook-test.yaml',
                        new ConfigVariablesFormatter(
                            __DIR__ . '/../..',
                        ),
                    ),
                ),
                new ProcessTimeMetric(),
            ),
        );
    }
}
