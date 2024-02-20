<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Output;

use Sonrac\Tools\PhpHook\HookRunner\Utils\CommandProcess;
use Sonrac\Tools\PhpHook\Runner\Process\AbstractProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResultRenderer
{
    /**
     * @var AbstractProcess[]
     */
    private array $processes;
    private ResultTable $resultTable;
    private SymfonyStyle $style;

    public function __construct(
        SymfonyStyle $style,
        AbstractProcess ...$process
    ) {
        $this->style = $style;
        $this->processes = $process;
        $this->resultTable = new ResultTable($this->style);
    }

    public function renderResultTable(): void
    {
        $this->resultTable->render(
            $this->getCommandsMap(),
        );
    }

    /**
     * @return array<string, int>
     */
    private function getCommandsMap(): array
    {
        $commandsMap = [];

        foreach ($this->processes as $process) {
            assert($process instanceof CommandProcess);
            $commandName = $process->getDefinition()->getName();

            $commandsMap[$commandName] = $process->getProcess()->getExitCode() ?? Command::SUCCESS;
        }

        return $commandsMap;
    }

    public function renderErrors(): bool
    {
        $hasErrors = false;
        foreach ($this->processes as $process) {
            if (!$process->isFailed()) {
                continue;
            }

            $hasErrors = true;

            $this->style->error([
                '_________________________________________',
                'Command name: ' . $process->getName(),
                'Command shell: ' . implode(' ', $process->getStartCommand()),
                $process->getOutput(),
                $process->getErrorOutput(),
                '_________________________________________',
            ]);
        }

        return $hasErrors;
    }
}
