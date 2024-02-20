<?php

declare(strict_types=1);

namespace App\Tools\Runner\Process;

use RuntimeException;

final class ProcessTimeMetric implements ProcessTimeMetricInterface
{
    private float $started;

    private float $ended;

    private bool $isFinished = false;

    public function __construct(
        ?float $started = null,
    ) {
        $this->started = $started ?? microtime(true);
    }

    public function getStarted(): float
    {
        return $this->started;
    }

    public function getEnded(): float
    {
        if (!$this->isFinished) {
            throw new RuntimeException('Process not finished yet');
        }

        return $this->ended;
    }

    public function executionTime(): float
    {
        if (!$this->isFinished) {
            throw new RuntimeException('Finish timer before');
        }

        return $this->ended - $this->started;
    }

    public function finish(): void
    {
        if ($this->isFinished) {
            throw new RuntimeException('Timer already finished');
        }

        $this->isFinished = true;
        $this->ended = microtime(true);
    }

    public function updateStartedTime(float $startTime): void
    {
        if ($this->isFinished) {
            throw new RuntimeException('Timer already finished');
        }

        $this->started = $startTime;
    }
}
