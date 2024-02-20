<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetric;

final class ProcessTimeMetricTest extends TestCase
{
    public function testExceptionFinish(): void
    {
        self::expectException(RuntimeException::class);
        $metric = new ProcessTimeMetric();
        $metric->finish();
        $metric->finish();
    }

    public function testExceptionExecutionTime(): void
    {
        self::expectException(RuntimeException::class);
        (new ProcessTimeMetric())->executionTime();
    }

    public function testExceptionGetEndedTime(): void
    {
        self::expectException(RuntimeException::class);
        (new ProcessTimeMetric())->getEnded();
    }

    public function testExceptionUpdateStartTime(): void
    {
        self::expectException(RuntimeException::class);
        $metric = new ProcessTimeMetric();
        $metric->finish();
        $metric->updateStartedTime(microtime(true));
    }

    public function testUpdateStartTime(): void
    {
        $time = microtime(true);

        $metric = new ProcessTimeMetric();
        $metric->updateStartedTime($time);
        $metric->finish();

        self::assertEquals($time, $metric->getStarted());
        self::assertTrue($metric->getStarted() <= $metric->getEnded());
    }
}
