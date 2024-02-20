<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Config;

use InvalidArgumentException;
use RuntimeException;
use Sonrac\Tools\PhpHook\HookRunner\Utils\CommandProcess;
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
     * @var array<string|int, string|int|bool|float|null|array<int|string, string>>
     */
    private array $config;
    private string $configFile;
    private ConfigVariablesFormatter $configVariablesFormatter;

    public function __construct(
        string $configFile,
        ConfigVariablesFormatter $configVariablesFormatter
    ) {
        $this->configVariablesFormatter = $configVariablesFormatter;
        $this->configFile = $configFile;
        if (!file_exists($this->configFile)) {
            throw new InvalidArgumentException(
                sprintf("Config %s does not exists", $this->configFile),
            );
        }

        /** @var array<string|int, string|int|bool|float|null|array<int|string, string>> $data */
        $data = Yaml::parseFile($this->configFile);

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
     *          cmd: array<string, string>,
     *          envFile: ?string,
     *          env: array<string, int|bool|string|float|null>,
     *          reverseOutput: bool,
     *          timeout: int,
     *          cwd: ?string,
     *          includeFiles: bool,
     *          forceDisableAttachArgs: bool,
     *          includeFilesPattern: ?array<string, string>,
     *      }>
     * }
     */
    public function readMainSection(): array
    {
        /** @var array<string, string|int|bool|float|null> $globalEnv */
        $globalEnv = $this->config[self::GLOBAL_ENV] ?? [];

        return [
            self::NAME => $this->getString($this->config, self::NAME),
            self::DESCRIPTION => $this->getStringOrNull($this->config, self::DESCRIPTION) ?? '',
            self::GLOBAL_ENV => $globalEnv,
            self::GLOBAL_ENV_FILE => $this->getStringOrNull($this->config, self::GLOBAL_ENV_FILE),
            self::COMMANDS => $this->readCommands(),
        ];
    }

    /**
     * phpcs:disable Generic.Files.LineLength.TooLong
     * @param array<int|string, array<int|string, string>|bool|float|int|string|null>|array<string, array<string, bool|float|int|string|null>|bool|int|string|null> $config
     * phpcs:enable Generic.Files.LineLength.TooLong
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
     * phpcs:disable Generic.Files.LineLength.TooLong
     * @param array<int|string, array<int|string, string>|bool|float|int|string|null>|array<string, array<string, bool|float|int|string|null>|bool|int|string|null> $config
     * phpcs:enable Generic.Files.LineLength.TooLong
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
     * @return array<int, array{
     *     name: string,
     *     description: string,
     *     errorMsg: ?string,
     *     cmd: array<string, string>,
     *     envFile: ?string,
     *     env: array<string, int|bool|string|float|null>,
     *     reverseOutput: bool,
     *     timeout: int,
     *     cwd: ?string,
     *     includeFiles: bool,
     *     forceDisableAttachArgs: bool,
     *     includeFilesPattern: ?array<string, string>,
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
            /** @var array{
             *      name: ?string,
             *      description: ?string,
             *      errorMsg: ?string,
             *      cmd: ?array<string, string>,
             *      envFile: ?string,
             *      env: ?array<string, int|bool|string|float|null>,
             *      reverseOutput: ?bool,
             *      timeout: ?int,
             *      cwd: ?string,
             *      includeFiles: ?bool,
             *      forceDisableAttachArgs: ?bool,
             *      includeFilesPattern: ?array<string, string>,
             * } $nextCommandConfig
             */
            if (
                !is_array($nextCommandConfig[self::COMMANDS_CMD]) ||
                0 === count($nextCommandConfig[self::COMMANDS_CMD])
            ) {
                throw new RuntimeException('Empty commands. Check your config file');
            }

            /** @var array<string, string|int|bool|float|null> $env */
            $env = $this->config[self::COMMANDS_ENV] ?? [];
            assert(array_key_exists(self::COMMANDS_CMD, $nextCommandConfig));
            /** @var string[] $cmd */
            $cmd = $nextCommandConfig[self::COMMANDS_CMD];
            /** @var array<string, string> $filePatterns */
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
                    false,
                ),
                self::COMMANDS_FILES_PATTERN => $filePatterns,
            ];
        }

        return $commands;
    }

    /**
     * @param array<string, array<string, bool|float|int|string|null>|bool|int|string|null> $config
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

    public function getFormatter(): ConfigVariablesFormatter
    {
        return $this->configVariablesFormatter;
    }

    public function getConfigFile(): string
    {
        return $this->configFile;
    }

    public function changeConfigFile(string $configFile): void
    {
        $this->configFile = $configFile;
    }
}
