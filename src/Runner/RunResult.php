<?php

declare(strict_types=1);

namespace App\Tools\Runner;

use App\Tools\Runner\Process\AbstractProcess;
use App\Tools\Runner\Process\ProcessTimeMetricInterface;

final readonly class RunResult
{
    /**
     * @var AbstractProcess[]
     */
    public array $processes;

    public function __construct(
        public ProcessTimeMetricInterface $processTimeMetric,
        public bool $isSuccessfully,
        AbstractProcess ...$process
    ) {
        $this->processes = $process;
    }
}
