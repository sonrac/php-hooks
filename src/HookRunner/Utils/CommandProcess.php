<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Utils;

use Sonrac\Tools\PhpHook\HookRunner\Definition\CommandDefinition;
use Sonrac\Tools\PhpHook\Runner\Process\AbstractProcess;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;

final class CommandProcess extends AbstractProcess
{
    public const DEFAULT_TIMEOUT = 60 * 30;
    public const TYPE_ERR = 'err';

    private string $stdOutput = '';
    private string $errorOutput = '';
    private CommandDefinition $definition;

    /**
     * @param string[]                             $command
     * @param array<string, string|int|float|bool> $env
     */
    public function __construct(
        array $command,
        array $env,
        CommandDefinition $definition,
        bool $reverseErrorOutput,
        ProcessTimeMetricInterface $processTimeMetric
    ) {
        $this->definition = $definition;
        parent::__construct(
            array_merge(
                $command,
                $this->definition->getArgs(),
            ),
            $env,
            $this->definition->getName(),
            $processTimeMetric,
            $this->definition->getCwd(),
            $this->definition->getTimeout(),
            $reverseErrorOutput,
        );
    }

    public function getDefinition(): CommandDefinition
    {
        return $this->definition;
    }

    protected function startCallback(string $type, string $data): void
    {
        if (self::TYPE_ERR === $type) {
            $this->errorOutput .= $data;

            return;
        }

        $this->stdOutput .= $data;
    }

    public function getOutput(): string
    {
        return $this->getStdOutput();
    }

    public function getStdOutput(): string
    {
        if ($this->reverseErrorOutput) {
            return $this->errorOutput;
        }

        return $this->stdOutput;
    }

    public function getErrorOutput(): string
    {
        if ($this->reverseErrorOutput) {
            return $this->stdOutput;
        }

        return $this->errorOutput;
    }
}
