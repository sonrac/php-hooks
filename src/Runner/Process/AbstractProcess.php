<?php

declare(strict_types=1);

namespace App\Tools\Runner\Process;

use App\Tools\Runner\Enum\ExitCodesEnum;
use Closure;
use Symfony\Component\Process\Process;

abstract class AbstractProcess
{
    private Process $process;
    private bool $stopped = false;

    /**
     * @param array<string, string|int|float|bool> $env
     */
    public function __construct(
        /** @var string[] */
        private readonly array $command,
        array $env,
        public readonly string $name,
        public readonly ProcessTimeMetricInterface $processTimeMetric,
        ?string $cwd = null,
        int $timeout = 30 * 60,
        public readonly bool $reverseErrorOutput = false,
    ) {
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
        return ExitCodesEnum::success !== ExitCodesEnum::getFromCode(
            true,
            $this->process->getExitCode(),
        );
    }

    public function getErrorOutput(): string
    {
        if ($this->reverseErrorOutput) {
            return $this->process->getOutput();
        }

        return $this->process->getErrorOutput();
    }

    /**
     * @return string[]
     */
    public function getStartCommand(): array
    {
        return $this->command;
    }
}
