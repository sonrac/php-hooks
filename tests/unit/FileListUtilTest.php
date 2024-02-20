<?php

declare(strict_types=1);

namespace Sonrac\Tools\PhpHook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonrac\Tools\PhpHook\HookRunner\Utils\FileListUtil;

final class FileListUtilTest extends TestCase
{
    private FileListUtil $util;

    public function testGetFilesByExt(): void
    {
        $filesList = $this->util->getFilesByExt('php');

        self::assertNotEmpty($filesList);
        self::assertEquals(1, substr_count($filesList[3], 'HookRunner/Utils/FileListUtil.php'));
    }

    public function testEmptyGetFilesByExt(): void
    {
        $filesList = $this->util->getFilesByExt('ini');

        self::assertEmpty($filesList);
    }

    public function testCountFile(): void
    {
        self::assertEquals(5, $this->util->count());
    }

    public function testGetItems(): void
    {
        self::assertCount(5, $this->util->items());
    }

    public function testGetFilesByPattern(): void
    {
        $files = $this->util->getFilesListByPattern('/FileList');
        self::assertCount(2, $files);

        // Return from cache
        $files = $this->util->getFilesListByPattern('/FileList');
        self::assertCount(2, $files);

        $files = $this->util->getFilesListByPattern('/FileList', 'php');
        self::assertCount(1, $files);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->util = new FileListUtil(
            __DIR__ . '/../../src',
            ...[
            'HookRunner/Utils/CommandProcess.php',
            'bashrc',
            'config.ini',
            'HookRunner/Utils/FileListUtil.php',
            'HookRunner/Utils/FileListUtil._php',
            ],
        );
    }
}
