<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config;

use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Utils\CommandProcess;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class ConfigReader
{
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const GLOBAL_ENV_FILE = 'globalEnvFile';
    public const GLOBAL_ENV = 'globalEnv';
    public const COMMANDS = 'commands';
    public const COMMANDS_NAME = 'name';
    public const COMMANDS_DESCRIPTION = 'description';
    public const COMMANDS_CMD = 'cmd';
    public const COMMANDS_ENV_FILE = 'envFile';
    public const COMMANDS_ENV = 'env';
    public const COMMANDS_ERROR_MSG = 'errorMsg';
    public const COMMANDS_REVERSE_OUTPUT = 'reverseOutput';
    public const COMMANDS_TIMEOUT = 'timeout';
    public const COMMANDS_CWD = 'cwd';
    public const COMMANDS_INCLUDE_FILES = 'includeFiles';
    public const COMMANDS_FILES_PATTERN = 'includeFilesPattern';
    public const COMMANDS_FORCE_DISABLE_ATTACH_ARGS = 'forceDisableAttachArgs';

    /**
     * @var array<string|int, string|int|bool|float|null>
     */
    private array $config;
    private string $configPath;
    private ConfigVariablesFormatter $configVariablesFormatter;

    public function __construct(
        string $configPath,
        ConfigVariablesFormatter $configVariablesFormatter
    ) {
        $this->configVariablesFormatter = $configVariablesFormatter;
        $this->configPath = $configPath;
        if (!file_exists($this->configPath)) {
            throw new InvalidArgumentException(
                sprintf("Config %s does not exists", $this->configPath),
            );
        }

        /** @var array<string|int, string|int|bool|float|null> $data */
        $data = Yaml::parseFile($this->configPath);

        $this->config = $this->configVariablesFormatter->format($data);
    }

    /**
     * @return array{
     *     name: string,
     *     description: string,
     *     globalEnvFile: ?string,
     *     globalEnv: array<string, int|bool|string|float|null>,
     *     commands: array<int, array{
     *          name: string,
     *          description: string,
     *          errorMsg: ?string,
     *          cmd: array<int, string>,
     *          envFile: ?string,
     *          env: array<string, int|bool|string|float|null>,
     *          reverseOutput: bool,
     *          timeout: int,
     *          cwd: ?string,
     *          includeFiles: bool,
     *          forceDisableAttachArgs: bool,
     *          includeFilesPattern: array<string|int, string>,
     *      }>
     * }
     */
    public function readMainSection(): array
    {
        /** @var array<string, string|int|bool|float|null> $globalEnv */
        $globalEnv = $this->config[self::GLOBAL_ENV] ?? [];

        return [
            self::NAME => (string)($this->config[self::NAME] ?? ''),
            self::DESCRIPTION => (string)($this->config[self::DESCRIPTION] ?? ''),
            self::GLOBAL_ENV => $globalEnv,
            self::GLOBAL_ENV_FILE => $this->getStringOrNull($this->config, self::GLOBAL_ENV_FILE, null),
            self::COMMANDS => $this->readCommands(),
        ];
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     description: string,
     *     errorMsg: ?string,
     *     cmd: array<int, string>,
     *     envFile: ?string,
     *     env: array<string, int|bool|string|float|null>,
     *     reverseOutput: bool,
     *     timeout: int,
     *     cwd: ?string,
     *     includeFiles: bool,
     *     forceDisableAttachArgs: bool,
     *     includeFilesPattern: array<string|int, string>,
     * }>
     */
    public function readCommands(): array
    {
        if (!isset($this->config[self::COMMANDS])) {
            return [];
        }

        if (!is_array($this->config[self::COMMANDS])) {
            throw new RuntimeException('Invalid commands configuration');
        }

        $commands = [];
        foreach ($this->config[self::COMMANDS] as $nextCommandConfig) {
            if (
                !is_array($nextCommandConfig[self::COMMANDS_CMD]) ||
                0 === count($nextCommandConfig[self::COMMANDS_CMD])
            ) {
                throw new RuntimeException('Empty commands. Check your config file');
            }

            /** @var array<string, string|int|bool|float|null> $env */
            $env = $this->config[self::COMMANDS_ENV] ?? [];
            /** @var string[] $cmd */
            $cmd = $nextCommandConfig[self::COMMANDS_CMD];
            /** @var array<string|int, string> $filePatterns */
            $filePatterns = $nextCommandConfig[self::COMMANDS_FILES_PATTERN] ?? [];

            $commands[] = [
                self::COMMANDS_NAME => $this->getString($nextCommandConfig, self::COMMANDS_NAME),
                self::COMMANDS_DESCRIPTION => $this->getString($nextCommandConfig, self::COMMANDS_DESCRIPTION),
                self::COMMANDS_ENV_FILE => $this->getStringOrNull($nextCommandConfig, self::COMMANDS_ENV_FILE, null),
                self::COMMANDS_ENV => $env,
                self::COMMANDS_CMD => $cmd,
                self::COMMANDS_REVERSE_OUTPUT => $this->getBool(
                    $nextCommandConfig,
                    self::
                    COMMANDS_REVERSE_OUTPUT,
                    false,
                ),
                self::COMMANDS_ERROR_MSG => $this->getStringOrNull($nextCommandConfig, self::COMMANDS_ERROR_MSG, '',),
                self::COMMANDS_TIMEOUT => $nextCommandConfig[self::COMMANDS_TIMEOUT] ??
                    CommandProcess::DEFAULT_TIMEOUT,
                self::COMMANDS_CWD => $this->getStringOrNull($nextCommandConfig, self::COMMANDS_CWD, null,),
                self::COMMANDS_INCLUDE_FILES => $this->getBool($nextCommandConfig, self::COMMANDS_INCLUDE_FILES, true,),
                self::COMMANDS_FORCE_DISABLE_ATTACH_ARGS => $this->getBool(
                    $nextCommandConfig,
                    self::COMMANDS_FORCE_DISABLE_ATTACH_ARGS,
                    true,
                ),
                self::COMMANDS_FILES_PATTERN => $filePatterns,
            ];
        }

        return $commands;
    }

    /**
     * @param array<string|int, string|int|bool|float|null> $config
     */
    private function getString(array $config, string $key): string
    {
        if (!array_key_exists($key, $config)) {
            return '';
        }

        $data = $config[$key];

        if (null === $data) {
            return '';
        }

        assert(is_string($data));

        return $data;
    }

    /**
     * @param array<string|int, string|int|bool|float|null> $config
     */
    private function getStringOrNull(array $config, string $key, ?string $defValue = null): ?string
    {
        if (!array_key_exists($key, $config)) {
            return $defValue;
        }

        $data = $config[$key];

        if (null === $data) {
            return $defValue;
        }

        assert(is_string($data));

        return $data;
    }

    /**
     * @param array<string|int, string|int|bool|float|null> $config
     */
    private function getBool(array $config, string $key, bool $defValue): bool
    {
        if (!array_key_exists($key, $config)) {
            return $defValue;
        }

        $data = $config[$key];

        if (null === $data) {
            return $defValue;
        }

        assert(is_bool($data));

        return $data;
    }
}
