<?php

declare(strict_types=1);

namespace App\Tools\Runner;

use App\Tools\Runner\Enum\ExitCodesEnum;
use App\Tools\Runner\Process\AbstractProcess;
use App\Tools\Runner\Process\ProcessTimeMetric;
use Closure;

final class ParallelRunner
{
    private bool $stopped = false;
    /**
     * @var AbstractProcess[]
     */
    private readonly array $processes;
    private readonly ProcessTimeMetric $processTimeMetric;

    public function __construct(
        private readonly ?Closure $startCallback = null,
        private readonly ?Closure $finishCallback = null,
        private readonly ?Closure $errorCallback = null,
        ?ProcessTimeMetric $processTimeMetric = null,
        AbstractProcess ...$abstractProcess,
    ) {
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

            exit(ExitCodesEnum::generalError->value);
        };
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, $sigHandler);
    }

    public function run(): RunResult
    {
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

            $this->checkFailedProcesses();
        } finally {
            foreach ($this->processes as $process) {
                $process->clear();
            }
        }

        $this->processTimeMetric->finish();

        return new RunResult($this->processTimeMetric, true, ...$this->processes);
    }

    private function checkFailedProcesses(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isFailed() && null !== $this->errorCallback) {
                call_user_func_array($this->errorCallback, [$process]);
            }
        }
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
}
