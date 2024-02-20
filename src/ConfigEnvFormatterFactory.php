<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook;

use Sonrac\Tools\PhpHook\HookRunner\Config\ConfigVariablesFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ConfigEnvFormatterFactory
{
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function __invoke(): ConfigVariablesFormatter
    {
        /** @var array<string, string> $variables */
        $variables = $this->container->getParameter('templateVariables');

        if (!is_array($variables)) {
            $variables = [];
        }

        $projectDir = $this->container->getParameter('projectDir');
        assert(is_string($projectDir));

        return new ConfigVariablesFormatter($projectDir, $variables);
    }
}
