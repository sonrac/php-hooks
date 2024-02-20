<?php

declare(strict_types=1);

namespace Sonrac\Tools\PreCommitHook\PreCommitHookRunner\Utils;

use const PATHINFO_EXTENSION;

final class FileListUtil
{
    public const EXT_PHP = 'php';

    /** @var string[] */
    private array $files;

    /**
     * @var array<string, array<string>>
     */
    private array $cachedFiles = [];
    private string $baseDir;

    public function __construct(
        string $baseDir,
        string ...$files
    ) {
        $this->baseDir = $baseDir;
        $this->files = $files;
    }

    /**
     * @return string[]
     */
    public function items(): array
    {
        return $this->files;
    }

    /**
     * @return string[]
     */
    public function getFilesByExt(string $neededExt): array
    {
        return array_map(
            function ($nextFile): string {
                return sprintf('%s/%s', $this->baseDir, $nextFile);
            },
            array_filter(
                $this->files,
                function (string $nextFile) use ($neededExt): bool {
                    $ext = pathinfo($nextFile, PATHINFO_EXTENSION);

                    $nextFile = sprintf('%s/%s', $this->baseDir, $nextFile);

                    return strtolower($neededExt) === strtolower($ext) && is_file($nextFile);
                }
            )
        );
    }

    public function count(): int
    {
        return count($this->files);
    }

    /**
     * @return string[]
     */
    public function getFilesListByPattern(string $pattern, ?string $extension = null): array
    {
        if (isset($this->cachedFiles[$pattern.$extension])) {
            return $this->cachedFiles[$pattern.$extension];
        }

        $files = [];

        foreach ($this->files as $file) {
            if (
                !preg_match(
                    sprintf('/%s/im', str_replace('/', '\/', $pattern)),
                    $file,
                )
            ) {
                continue;
            }

            $fileExt = pathinfo($file, PATHINFO_EXTENSION);

            if (null !== $extension && strtolower($extension) !== strtolower($fileExt)) {
                continue;
            }

            $files[] = $file;
        }

        return $this->cachedFiles[$pattern] = $files;
    }
}
