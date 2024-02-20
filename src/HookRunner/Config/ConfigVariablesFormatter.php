<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config;

final class ConfigVariablesFormatter
{
    /**
     * @var array<string, string>
     */
    private array $variables;

    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        ?string $projectDir = null,
        string ...$variables
    ) {
        $this->variables = $variables;
        $this->variables['%composer_cmd%'] = trim($this->findCommandLocation('composer'));
        $this->variables['%php_cmd%'] = trim($this->findCommandLocation('php'));

        if (!isset($this->variables['%project_dir%'])) {
            $this->variables['%project_dir%'] = $projectDir ?? trim(dirname(__DIR__, 4));
        }
    }

    /**
     * @param array<string|int, string|int|bool|float|null> $data
     *
     * @return array<string|int, string|int|bool|float|null>
     */
    public function format(array $data): array
    {
        foreach ($data as &$nextValue) {
            /** @var array<string|int, string|int|bool|float|null> $nextValue */
            if (is_array($nextValue)) {
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
            $nextValue = str_replace($pattern, $variable, $nextValue);
        }

        return $nextValue;
    }

    private function findCommandLocation(string $cmd): string
    {
        $result = exec('which '.$cmd, $output, $resultCode);

        if (false !== $result && 0 === $resultCode) {
            return trim($result);
        }

        return '';
    }
}
