<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\Env;

final class EnvVariables
{
    /**
     * @var array<string, string|int|bool|float|null> $variables
     */
    private array $variables;

    /**
     * @param array<string, string|int|bool|float|null> $variables
     */
    public function __construct(
        array $variables
    ) {
        $this->variables = $variables;
    }

    /**
     * @param string|int|bool|float $value
     */
    public function add(string $name, $value): void
    {
        $this->variables[mb_strtoupper($name)] = $value;
    }

    public function get(string $name): ?string
    {
        if (!isset($this->variables[mb_strtoupper($name)])) {
            return null;
        }

        if (is_bool($this->variables[mb_strtoupper($name)])) {
            return $this->variables[mb_strtoupper($name)] ? 'true' : 'false';
        }

        return (string)$this->variables[mb_strtoupper($name)];
    }

    /**
     * @return array<string, string|int|bool|float|null>
     */
    public function all(): array
    {
        return $this->variables;
    }

    /**
     * @return array<string, string>
     */
    public function __toArray(): array
    {
        $result = [];

        foreach ($this->variables as $name => $value) {
            if (null !== ($value = $this->get($name))) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function __toString(): string
    {
        $result = '';

        foreach ($this->__toArray() as $name => $value) {
            $result .= 0 === mb_strlen($result) ? '' : ' ';
            $result .= "{$name}=\"{$value}\"";
        }

        return $result;
    }

    public function merge(EnvVariables $env, bool $replace = true): void
    {
        foreach ($env->__toArray() as $name => $value) {
            if (false === $replace && array_key_exists($name, $this->variables)) {
                continue;
            }

            $this->add($name, $value);
        }
    }
}
