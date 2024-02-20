<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\HookRunner\Config\DTO;

use Sonrac\Tools\PhpHook\HookRunner\Config\Env\EnvVariables;
use Sonrac\Tools\PhpHook\HookRunner\Utils\CommandProcess;

final class CommandDto
{
    private string $name;
    private string $description;
    private ?string $errorMsg;

    /**
     * @var string[]
     */
    private array $cmd;
    private EnvVariables $env;
    private bool $reverseOutput;
    private bool $includeFiles = false;
    /**
     * @var array<string, string>
     */
    private array $includeFilesPatterns = [];
    private bool $forceDisableAttachArgs = false;
    private int $timeout = CommandProcess::DEFAULT_TIMEOUT;
    private ?string $cwd = null;

    /**
     * @param string[] $cmd
     * @param array<string, string> $includeFilesPatterns
     */
    public function __construct(
        string $name,
        string $description,
        ?string $errorMsg,
        /** @var string[] */
        array $cmd,
        EnvVariables $env,
        bool $reverseOutput,
        bool $includeFiles = false,
        /** @var array<int|string, string> */
        array $includeFilesPatterns = [],
        bool $forceDisableAttachArgs = false,
        int $timeout = CommandProcess::DEFAULT_TIMEOUT,
        ?string $cwd = null
    ) {
        $this->cwd = $cwd;
        $this->timeout = $timeout;
        $this->forceDisableAttachArgs = $forceDisableAttachArgs;
        $this->includeFilesPatterns = $includeFilesPatterns;
        $this->includeFiles = $includeFiles;
        $this->reverseOutput = $reverseOutput;
        $this->env = $env;
        $this->cmd = $cmd;
        $this->errorMsg = $errorMsg;
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

    public function getErrorMsg(): ?string
    {
        return $this->errorMsg;
    }

    /**
     * @return string[]
     */
    public function getCmd(): array
    {
        return $this->cmd;
    }

    public function getEnv(): EnvVariables
    {
        return $this->env;
    }

    public function isReverseOutput(): bool
    {
        return $this->reverseOutput;
    }

    public function isIncludeFiles(): bool
    {
        return $this->includeFiles;
    }

    /**
     * @return array<string, string>
     */
    public function getIncludeFilesPatterns(): array
    {
        return $this->includeFilesPatterns;
    }

    public function isForceDisableAttachArgs(): bool
    {
        return $this->forceDisableAttachArgs;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getCwd(): ?string
    {
        return $this->cwd;
    }
}
