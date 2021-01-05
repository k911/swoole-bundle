<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Runtime\HMR;

use Assert\InvalidArgumentException;
use K911\Swoole\Server\Runtime\HMR\InotifyHMR;
use PHPUnit\Framework\TestCase;

class InotifyHMRTest extends TestCase
{
    private const NON_EXISTING_FILE = __DIR__.'/not_exists.php';

    private const NON_RELOADABLE_EXISTING_FILES = [
        __DIR__.'/HMRSpy.php',
        __DIR__.'/InotifyHMRTest.php',
    ];

    protected function setUp(): void
    {
        if (!\extension_loaded('inotify')) {
            self::markTestSkipped('Swoole Bundle HMR requires "inotify" PHP extension present and installed on the system.');
        }
    }

    public function testConstructSetGetNonReloadableFiles(): void
    {
        $hmr = new InotifyHMR(self::NON_RELOADABLE_EXISTING_FILES);
        self::assertSame(self::NON_RELOADABLE_EXISTING_FILES, $hmr->getNonReloadableFiles());
    }

    public function testConstructSetNotExistingNonReloadableFiles(): void
    {
        self::assertFileDoesNotExist(self::NON_EXISTING_FILE);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('File "%s" was expected to exist.', self::NON_EXISTING_FILE));

        new InotifyHMR([self::NON_EXISTING_FILE]);
    }

    public function testBootSetGetNonReloadableFiles(): void
    {
        $hmr = new InotifyHMR();
        $hmr->boot(['nonReloadableFiles' => self::NON_RELOADABLE_EXISTING_FILES]);

        $expected = \array_unique(
            \array_merge(\get_included_files(), self::NON_RELOADABLE_EXISTING_FILES)
        );
        \sort($expected);
        $result = $hmr->getNonReloadableFiles();
        \sort($result);

        self::assertSame($result, $expected);
    }

    public function testBootSetNotExistingNonReloadableFiles(): void
    {
        self::assertFileDoesNotExist(self::NON_EXISTING_FILE);

        $hmr = new InotifyHMR();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('File "%s" was expected to exist.', self::NON_EXISTING_FILE));

        $hmr->boot(['nonReloadableFiles' => [self::NON_EXISTING_FILE]]);
    }
}
