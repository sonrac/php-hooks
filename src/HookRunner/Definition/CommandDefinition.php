<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Definition;

use Sonrac\Tools\PhpHook\HookRunner\Config\DTO\CommandDto;

final class CommandDefinition
{
    private string $name;
    private ?string $cwd;
    private int $timeout;
    private CommandDto $commandDto;
    /**
     * @var array<string|int, string>
     */
    private array $args = [];

    /**
     * @param array<string|int, string> $args
     */
    public function __construct(
        string $name,
        ?string $cwd,
        int $timeout,
        CommandDto $commandDto,
        array $args = []
    ) {
        $this->args = $args;
        $this->commandDto = $commandDto;
        $this->timeout = $timeout;
        $this->cwd = $cwd;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCwd(): ?string
    {
        return $this->cwd;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getCommandDto(): CommandDto
    {
        return $this->commandDto;
    }

    /**
     * @return string[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
