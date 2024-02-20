<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\Env;

use InvalidArgumentException;
use RuntimeException;

final class EnvVariablesFormatter
{
    /**
     * @var array<string, string|int|bool|float|null>
     */
    private array $globalEnv = [];

    /**
     * @param array<string, string|int|bool|float|null> $globalEnv
     */
    public function __construct(
        array $globalEnv = [],
        ?string $globalEnvFile = null
    ) {
        $this->globalEnv = $globalEnv;
        if (null !== $globalEnvFile) {
            $this->globalEnv = array_merge($this->globalEnv, $this->readEnvFromFile($globalEnvFile));
        }
    }

    /**
     * @param array<string, string|int|bool|float|null> $variables
     */
    public function formatEnv(
        array $variables = [],
        ?string $envFile = null,
        bool $mergeGlobal = true
    ): EnvVariables {
        $env = new EnvVariables([]);

        if (true === $mergeGlobal && 0 !== count($this->globalEnv)) {
            $env->merge($this->formatEnvFromArray($this->globalEnv));
        }

        if (0 !== count($variables)) {
            $env->merge($this->formatEnvFromArray($variables));
        }

        if (null !== $envFile) {
            $env->merge($this->formatEnvFromArray(
                $this->readEnvFromFile($envFile)
            ));
        }

        return $env;
    }

    /**
     * @param array<string, string|int|bool|float|null> $variables
     */
    private function formatEnvFromArray(array $variables): EnvVariables
    {
        return new EnvVariables($variables);
    }

    /**
     * @return array<string, string|int|bool|float|null>
     */
    private function readEnvFromFile(string $envFile): array
    {
        if (!file_exists($envFile)) {
            throw new InvalidArgumentException(
                sprintf('File %s does not found', $envFile),
            );
        }

        $data = parse_ini_file($envFile);

        if (false === $data) {
            throw new RuntimeException(
                sprintf('File %s did not read', $envFile),
            );
        }

        return $data;
    }
}
