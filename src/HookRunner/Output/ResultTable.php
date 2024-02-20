<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Output;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResultTable
{
    private SymfonyStyle $style;

    public function __construct(
        SymfonyStyle $style
    ) {
        $this->style = $style;
    }

    /**
     * @param array<string, int> $commandsMap
     */
    public function render(
        array $commandsMap
    ): void {
        $table = $this->style->createTable();
        $table->setStyle('box');

        $table->setHeaders(['command', 'result']);

        $hasErrors = false;

        foreach ($commandsMap as $command => $result) {
            $nextRow = [
                $command
            ];

            if (Command::SUCCESS !== $result) {
                $hasErrors = true;
                $nextRow[] = $this->getErrorResult();
            } else {
                $nextRow[] = $this->getSuccessResult();
            }

            $table->addRow($nextRow);
        }

        $table->render();

        if ($hasErrors) {
            $this->style->error('See errors below this result table');
        } else {
            $this->style->success('Pre hook commit run successfully');
        }
    }

    private function getSuccessResult(): string
    {
        return '<bg=green;fg=black;>  √  </>';
    }

    private function getErrorResult(): string
    {
        return "<bg=red;fg=black;>  X  </>";
    }
}
