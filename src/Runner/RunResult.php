<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Runner;

use Sonrac\Tools\PhpHook\Runner\Process\AbstractProcess;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;

final class RunResult
{
    /**
     * @var AbstractProcess[]
     */
    private array $processes;
    private ProcessTimeMetricInterface $processTimeMetric;
    private bool $isSuccessfully;

    public function __construct(
        ProcessTimeMetricInterface $processTimeMetric,
        bool $isSuccessfully,
        AbstractProcess ...$process
    ) {
        $this->isSuccessfully = $isSuccessfully;
        $this->processTimeMetric = $processTimeMetric;
        $this->processes = $process;
    }

    /**
     * @return AbstractProcess[]
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }

    public function getProcessTimeMetric(): ProcessTimeMetricInterface
    {
        return $this->processTimeMetric;
    }

    public function isSuccessfully(): bool
    {
        return $this->isSuccessfully;
    }
}
