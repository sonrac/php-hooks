<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config;

use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\DTO\CommandDto;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\DTO\PreCommitHookDto;
use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\Env\EnvVariablesFormatter;

final class ConfigBuilder
{
    private ConfigReader $configReader;

    public function __construct(
        ConfigReader $configReader
    ) {
        $this->configReader = $configReader;
    }

    public function build(): PreCommitHookDto
    {
        $config = $this->configReader->readMainSection();

        $envVarFormatter = new EnvVariablesFormatter(
            $config[ConfigReader::GLOBAL_ENV] ?? [],
            $config[ConfigReader::GLOBAL_ENV_FILE] ?? null,
        );

        return new PreCommitHookDto(
            $config[ConfigReader::NAME],
            $config[ConfigReader::DESCRIPTION],
            $envVarFormatter->formatEnv(),
            $this->buildCommands($config[ConfigReader::COMMANDS], $envVarFormatter),
        );
    }

    /**
     * @param array<int, array{
     *      name: string,
     *      description: string,
     *      errorMsg: ?string,
     *      cmd: array<int, string>,
     *      envFile: ?string,
     *      env: array<string, string|int|bool|float|null>,
     *      reverseOutput: bool,
     *      timeout: int,
     *      cwd: ?string,
     *      includeFiles: bool,
     *      forceDisableAttachArgs: bool,
     *      includeFilesPattern: string[],
     *  }> $commands
     *
     * @return CommandDto[]
     */
    private function buildCommands(array $commands, EnvVariablesFormatter $envVarsFormatter): array
    {
        $cmdDtos = [];

        foreach ($commands as $nextCmd) {
            $cmdDtos[] = new CommandDto(
                $nextCmd[ConfigReader::COMMANDS_NAME],
                $nextCmd[ConfigReader::COMMANDS_DESCRIPTION],
                $nextCmd[ConfigReader::COMMANDS_ERROR_MSG],
                $nextCmd[ConfigReader::COMMANDS_CMD],
                $envVarsFormatter->formatEnv(
                    $nextCmd[ConfigReader::COMMANDS_ENV],
                    $nextCmd[ConfigReader::COMMANDS_ENV_FILE],
                    false
                ),
                $nextCmd[ConfigReader::COMMANDS_REVERSE_OUTPUT],
                $nextCmd[ConfigReader::COMMANDS_INCLUDE_FILES],
                $nextCmd[ConfigReader::COMMANDS_FILES_PATTERN] ?? [],
                $nextCmd[ConfigReader::COMMANDS_FORCE_DISABLE_ATTACH_ARGS],
                $nextCmd[ConfigReader::COMMANDS_TIMEOUT],
                $nextCmd[ConfigReader::COMMANDS_CWD],
            );
        }

        return $cmdDtos;
    }
}
