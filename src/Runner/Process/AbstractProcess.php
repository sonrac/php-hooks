<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Runner\Process;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

abstract class AbstractProcess
{
    public bool $reverseErrorOutput = false;
    private ProcessTimeMetricInterface $processTimeMetric;
    private string $name;
    private Process $process;
    private bool $stopped = false;
    /**
     * @var string[]
     */
    private array $command;

    /**
     * @param string[] $command
     * @param array<string, string|int|float|bool> $env
     */
    public function __construct(
        array $command,
        array $env,
        string $name,
        ProcessTimeMetricInterface $processTimeMetric,
        ?string $cwd = null,
        int $timeout = 30 * 60,
        bool $reverseErrorOutput = false
    ) {
        $this->reverseErrorOutput = $reverseErrorOutput;
        $this->command = $command;
        $this->name = $name;
        $this->processTimeMetric = $processTimeMetric;
        $this->process = new Process(
            $command,
            $cwd,
            $env,
            null,
            $timeout,
        );
    }

    public function getCommand(): string
    {
        $envs = [];
        foreach ($this->process->getEnv() as $env => $value) {
            $envs[] = $env . '=' . $value;
        }

        return implode(' ', $envs) . ' ' . $this->process->getCommandLine();
    }

    public function start(?Closure $startCallback): Process
    {
        $this->process->start(function (string $type, string $data) use ($startCallback): void {
            $this->startCallback($type, $data);
            if (null !== $startCallback) {
                $startCallback($type, $data);
            }
        });

        return $this->process;
    }

    abstract protected function startCallback(string $type, string $data): void;

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getOutput(): string
    {
        if ($this->reverseErrorOutput) {
            return $this->process->getErrorOutput();
        }

        return $this->process->getOutput();
    }

    public function getErrorOutput(): string
    {
        if ($this->reverseErrorOutput) {
            return $this->process->getOutput();
        }

        return $this->process->getErrorOutput();
    }

    public function wait(): void
    {
        $this->process->wait();
    }

    public function clear(): void
    {
        if ($this->stopped) {
            return;
        }

        $this->stopped = true;
        if ($this->process->getPid() !== null) {
            posix_kill($this->process->getPid(), SIGKILL);
        }
    }

    public function isFailed(): bool
    {
        return Command::SUCCESS !== $this->process->getExitCode();
    }

    /**
     * @return string[]
     */
    public function getStartCommand(): array
    {
        return $this->command;
    }

    public function getProcessTimeMetric(): ProcessTimeMetricInterface
    {
        return $this->processTimeMetric;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isReverseErrorOutput(): bool
    {
        return $this->reverseErrorOutput;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}
