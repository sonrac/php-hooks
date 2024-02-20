<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook;

use Symfony\Component\DependencyInjection\Definition;

final class CommandDefinition extends Definition
{
    private string $name;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
