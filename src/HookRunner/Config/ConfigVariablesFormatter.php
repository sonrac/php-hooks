<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Config;

final class ConfigVariablesFormatter
{
    /**
     * @var array<string, string>
     */
    private array $variables;
    /**
     * @var array<string, string>
     */
    private array $originVariables;
    private string $projectDir;

    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        ?string $projectDir = null,
        array $variables = []
    ) {
        $this->projectDir = $projectDir ?? trim(dirname(__DIR__, 4));
        $this->variables = $variables;
        $this->originVariables = $variables;
        $this->variables['{composer_cmd}'] = trim($this->findCommandLocation('composer'));
        $this->variables['{php_cmd}'] = trim($this->findCommandLocation('php'));

        if (!array_key_exists('{project_dir}', $this->variables)) {
            $this->variables['{project_dir}'] = $this->projectDir;
        }
    }

    private function findCommandLocation(string $cmd): string
    {
        $result = exec('which ' . $cmd, $output, $resultCode);

        if (false !== $result && 0 === $resultCode) {
            return trim($result);
        }

        return '';
    }

    /**
     * @param array<string|int, string|int|bool|float|null|array<int|string, string>> $data
     *
     * @return array<string|int, string|int|bool|float|null|array<int|string, string>>
     */
    public function format(array $data): array
    {
        foreach ($data as &$nextValue) {
            if (is_array($nextValue)) {
                /** @var array<string|int, string|int|bool|float|null> $nextValue */
                $nextValue = $this->format($nextValue);
                continue;
            }

            if (!is_string($nextValue)) {
                continue;
            }

            $nextValue = $this->replaceValue($nextValue);
        }

        return $data;
    }

    private function replaceValue(string $nextValue): string
    {
        foreach ($this->variables as $pattern => $variable) {
            $nextValue = str_replace(
                $pattern,
                $variable,
                $nextValue,
            );
        }

        return $nextValue;
    }

    /**
     * @return array<string, string>
     */
    public function getVariables(): array
    {
        return $this->originVariables;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }
}
