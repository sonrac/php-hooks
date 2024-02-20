<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Commands;

use InvalidArgumentException;
use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigBuilder;
use Sonrac\Tools\PhpHook\HookRunner\Config\DTO\CommandDto;
use Sonrac\Tools\PhpHook\HookRunner\Config\Env\EnvVariables;
use Sonrac\Tools\PhpHook\HookRunner\Definition\CommandDefinition;
use Sonrac\Tools\PhpHook\HookRunner\Output\ResultRenderer;
use Sonrac\Tools\PhpHook\HookRunner\Utils\CommandProcess;
use Sonrac\Tools\PhpHook\HookRunner\Utils\FileListUtil;
use Sonrac\Tools\PhpHook\Runner\ParallelRunner;
use Sonrac\Tools\PhpHook\Runner\Process\AbstractProcess;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetric;
use Sonrac\Tools\PhpHook\Runner\Process\ProcessTimeMetricInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class HookCommand extends Command
{
    /**
     * @var string[]
     */
    private array $files = [];
    private FileListUtil $flleUtil;
    private ConfigBuilder $configBuilder;
    private ProcessTimeMetricInterface $processTimeMetric;

    public function __construct(
        ConfigBuilder $configBuilder,
        ProcessTimeMetricInterface $processTimeMetric
    ) {
        parent::__construct('hook');

        $this->configBuilder = $configBuilder;
        $this->processTimeMetric = $processTimeMetric;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Run hook command');
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Path to the configuration file',
            __DIR__ . '/../../config/hook.yaml',
        );
        $this->addOption(
            'project-dir',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Project root dir',
        );

        $this->addArgument(
            'files',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Files list to scan',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        /** @var ?string $projectDir */
        $projectDir = $input->getOption('project-dir');

        if (null !== $projectDir) {
            if (!is_dir($projectDir)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid project directory %s', $projectDir),
                );
            }

            $this->configBuilder->changeProjectDir($projectDir, $configFile);
        }

        $configDto = $this->configBuilder->build($configFile);

        $style->title($configDto->getName());
        $style->info($configDto->getDescription());

        $progressBar = new ProgressBar($output);
        $preparedProcesses = $this->createProcesses(
            $configDto->getGlobalEnv(),
            $output,
            ...$configDto->getCommands(),
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
            $this->processTimeMetric,
            ...$preparedProcesses,
        );

        $result = $runner->run();

        $progressBar->finish();
        $output->writeln('');

        $resultTable = new ResultRenderer($style, ...$result->getProcesses());

        if (!$result->isSuccessfully()) {
            $resultTable->renderErrors();
        }

        $resultTable->renderResultTable();
        $output->writeln(
            sprintf(
                'Execution time: %10.5f seconds',
                $result->getProcessTimeMetric()->executionTime(),
            ),
        );

        return $result->isSuccessfully() ? self::SUCCESS : self::FAILURE;
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
     * @param bool $includeFiles
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

        foreach ($includeFilesPatterns as $includeFilesPattern => $ext) {
            if (!is_string($includeFilesPattern)) {
                $files = array_merge(
                    $this->flleUtil->getFilesByExt($ext),
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
