<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Runner\Process;

interface ProcessTimeMetricInterface
{
    public function getStarted(): float;

    public function getEnded(): float;

    public function executionTime(): float;

    public function finish(): void;

    public function updateStartedTime(float $startTime): void;
}
