<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Output;

use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Utils\CommandProcess;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResultRenderer
{
    /**
     * @var CommandProcess[]
     */
    private array $processes;
    private ResultTable $resultTable;
    private SymfonyStyle $style;

    public function __construct(
        SymfonyStyle $style,
        CommandProcess ...$process
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

    public function renderErrors(): bool
    {
        $hasErrors = false;
        foreach ($this->processes as $process) {
            if (!$process->isFailed()) {
                continue;
            }

            $hasErrors = true;

            $this->style->error([
                'Command: ' . $process->getCommand(),
                $process->getProcess()->getOutput(),
            ]);
        }

        return $hasErrors;
    }

    /**
     * @return array<string, int>
     */
    private function getCommandsMap(): array
    {
        $commandsMap = [];

        foreach ($this->processes as $process) {
            $commandName = $process->getDefinition()->getName();

            $commandsMap[$commandName] = $process->getProcess()->getExitCode();
        }

        return $commandsMap;
    }
}
