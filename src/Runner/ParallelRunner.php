<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Runner;

use Closure;
use Sonrac\Tools\PhpHook\Runner\Process\AbstractProcess;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetric;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;
use Symfony\Component\Console\Command\Command;

final class ParallelRunner
{
    private bool $stopped = false;
    /**
     * @var AbstractProcess[]
     */
    private array $processes;
    private ProcessTimeMetricInterface $processTimeMetric;
    private ?Closure $finishCallback = null;
    private ?Closure $startCallback = null;
    private ?Closure $errorCallback = null;

    public function __construct(
        ?Closure $startCallback = null,
        ?Closure $finishCallback = null,
        ?Closure $errorCallback = null,
        ?ProcessTimeMetricInterface $processTimeMetric = null,
        AbstractProcess ...$abstractProcess
    ) {
        $this->errorCallback = $errorCallback;
        $this->startCallback = $startCallback;
        $this->finishCallback = $finishCallback;
        $this->processTimeMetric = $processTimeMetric ?? new ProcessTimeMetric();
        $this->processes = $abstractProcess;

        $sigHandler = function (): void {
            $this->stopped = true;
            $this->processTimeMetric->finish();

            foreach ($this->processes as $process) {
                if (null !== $this->errorCallback) {
                    call_user_func_array($this->errorCallback, [$process]);
                }
                $process->clear();
            }

            exit(Command::FAILURE);
        };

        if (function_exists('pcntl_async_signals')) {
            @pcntl_async_signals(true);
            @pcntl_signal(SIGINT, $sigHandler);
        }
    }

    public function run(): RunResult
    {
        $isOk = true;
        try {
            foreach ($this->processes as $process) {
                $process->start($this->startCallback);
            }

            $running = $this->processes;
            while (!$this->stopped) {
                foreach ($running as $k => $process) {
                    if ($this->isProcessSuccessfullyFinished($process)) {
                        unset($running[$k]);
                    }

                    usleep(30000);
                }

                if (count($running) === 0) {
                    break;
                }
            }

            if ($this->stopped) {
                $this->processTimeMetric->finish();

                return new RunResult($this->processTimeMetric, false, ...$this->processes);
            }

            $isOk  = $this->checkFailedProcesses();
        } finally {
            foreach ($this->processes as $process) {
                $process->clear();
            }
        }

        $this->processTimeMetric->finish();

        return new RunResult($this->processTimeMetric, $isOk, ...$this->processes);
    }

    private function isProcessSuccessfullyFinished(AbstractProcess $process): bool
    {
        if (!$process->getProcess()->isRunning()) {
            if (null !== $this->finishCallback && !$process->isFailed()) {
                call_user_func_array($this->finishCallback, [$process]);
            }

            return true;
        }

        return false;
    }

    private function checkFailedProcesses(): bool
    {
        $isOk = true;
        foreach ($this->processes as $process) {
            if ($process->isFailed() && null !== $this->errorCallback) {
                $isOk = false;
                call_user_func_array($this->errorCallback, [$process]);
            }
        }

        return $isOk;
    }
}
