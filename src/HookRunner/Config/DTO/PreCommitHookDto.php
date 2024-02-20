<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\DTO;

use Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Config\Env\EnvVariables;

final class PreCommitHookDto
{
    private string $name;
    private string $description;
    private EnvVariables $globalEnv;
    /**
     * @var CommandDto[]
     */
    private array $commands;

    /**
     * @param CommandDto[] $commands
     */
    public function __construct(
        string $name,
        string $description,
        EnvVariables $globalEnv,
        array $commands
    ) {
        $this->commands = $commands;
        $this->globalEnv = $globalEnv;
        $this->description = $description;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGlobalEnv(): EnvVariables
    {
        return $this->globalEnv;
    }

    /**
     * @return CommandDto[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
