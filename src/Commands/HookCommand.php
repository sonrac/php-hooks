<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\Commands;

use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\ConfigReader;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\ConfigVariablesFormatter;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\DTO\CommandDto;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\DTO\PreCommitHookDto;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\Env\EnvVariables;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Definition\CommandDefinition;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Output\ResultTable;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Utils\CommandProcess;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Utils\FileListUtil;
use Sonrac\Tools\PreCommitHook\Runner\ParallelRunner;
use Sonrac\Tools\PreCommitHook\Runner\Process\AbstractProcess;
use Sonrac\Tools\PreCommitHook\Runner\Process\ProcessTimeMetric;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PreCommitHookCommand extends Command
{
    /**
     * @var string[]
     */
    private array $files = [];
    private FileListUtil $flleUtil;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:git:pre-commit');
        $this->setDescription('Pre-commit hook command');
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Path to the configuration file',
            dirname(__DIR__, 2) . '/config/pre-commit-hook.yaml',
        );

        $this->addArgument(
            'files',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Files list to scan',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processTimeMetric = new ProcessTimeMetric();

        $style = new SymfonyStyle($input, $output);
        $files = $input->getArgument('files');

        assert(is_array($files));
        $this->files = $files;

        if (0 === count($this->files)) {
            $style->success('Nothing to check. Empty files list.');

            return self::SUCCESS;
        }

        $this->flleUtil = new FileListUtil(
            dirname(__DIR__, 3),
            ...$this->files,
        );

        $configFile = $input->getOption('config');

        if (!is_string($configFile) || !file_exists($configFile)) {
            $style->error('Config file not found');

            return self::INVALID;
        }

        $configDto = $this->prepareConfig($configFile);

        $style->title($configDto->getName());
        $style->info($configDto->getDescription());

        $progressBar = new ProgressBar($output);
        $preparedProcesses = $this->createProcesses(
            $configDto->getGlobalEnv(),
            $output,
            ...$configDto->getCommands()
        );
        $progressBar->start(count($preparedProcesses));

        $finishCallBack = function (AbstractProcess $process) use ($progressBar): void {
            $progressBar->advance();
        };
        $errorCallback = function (AbstractProcess $process) use ($progressBar): void {
            $progressBar->advance();
        };

        $runner = new ParallelRunner(
            null,
            $finishCallBack,
            $errorCallback,
            $processTimeMetric,
            ...$preparedProcesses,
        );

        $result = $runner->run();

        $progressBar->finish();
        $output->writeln('');

        $resultTable = new ResultTable($style);
        /** @var array<string, int> $commandsMap */
        $commandsMap = [];
        foreach ($result->getProcesses() as $process) {
            $commandsMap[$process->getName()] = $process->isFailed() ? self::INVALID : self::SUCCESS;
        }

        foreach ($result->getProcesses() as $process) {
            if ($process->isFailed()) {
                $output->writeln('_________________________________________');
                $output->writeln('Command name: ' . $process->getName());
                $output->writeln('Command shell: ' . implode(' ', $process->getStartCommand()));
                $output->writeln($process->getOutput());
                $output->writeln($process->getErrorOutput());
                $output->writeln('_________________________________________');
            }
        }

        $resultTable->render($commandsMap);
        $output->writeln(
            sprintf(
                'Execution time: %10.5f seconds',
                $result->getProcessTimeMetric()->getEnded() - $result->getProcessTimeMetric()->getStarted(),
            ),
        );

        return self::SUCCESS;
    }

    private function prepareConfig(string $configFile): PreCommitHookDto
    {
        $configReader = new ConfigReader($configFile, new ConfigVariablesFormatter([]));

        $configBuilder = new ConfigBuilder($configReader);

        return $configBuilder->build();
    }

    /**
     * @return AbstractProcess[]
     */
    private function createProcesses(EnvVariables $globalEnv, OutputInterface $output, CommandDto ...$commands): array
    {
        $processes = [];

        foreach ($commands as $command) {
            $fileList = $this->getFileList($command->isIncludeFiles(), $command->getIncludeFilesPatterns());

            if (0 === count($fileList) && $command->isIncludeFiles()) {
                $output->writeln(
                    sprintf('Skip command: %s', $command->getName()),
                );
                continue;
            }

            $env = new EnvVariables($globalEnv->__toArray());
            $env->merge($command->getEnv());
            $processes[] = new CommandProcess(
                $command->getCmd(),
                $env->__toArray(),
                new CommandDefinition(
                    $command->getName(),
                    $command->getCwd(),
                    $command->getTimeout(),
                    $command,
                    !$command->isForceDisableAttachArgs() ? $fileList : [],
                ),
                $command->isReverseOutput(),
                new ProcessTimeMetric(),
            );
        }

        return $processes;
    }

    /**
     * @param bool     $includeFiles
     * @param string[] $includeFilesPatterns
     *
     * @return string[]
     */
    private function getFileList(bool $includeFiles, array $includeFilesPatterns): array
    {
        if (false === $includeFiles) {
            return [];
        }

        $files = [];

        if (0 === count($includeFilesPatterns)) {
            return $this->files;
        }

        foreach ($includeFilesPatterns as $ext => $includeFilesPattern) {
            if (!is_string($ext)) {
                $files = array_merge(
                    $this->flleUtil->getFilesByExt($includeFilesPattern),
                    $files,
                );

                continue;
            }

            $files = array_merge(
                $this->flleUtil->getFilesListByPattern($includeFilesPattern, $ext),
                $files,
            );
        }

        return array_unique($files);
    }
}
