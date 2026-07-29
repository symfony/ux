<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconFactory;
use Symfony\UX\Icons\Registry\AutoLockIconRegistry;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;
use Symfony\UX\Icons\Tests\Util\InMemoryIconRegistry;

final class AutoLockIconRegistryTest extends TestCase
{
    private string $iconDir;

    protected function setUp(): void
    {
        $this->iconDir = sys_get_temp_dir().'/ux_icons_autolock_'.uniqid();
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->iconDir);
    }

    public function testReturnsIconFromInnerAndPersistsItToDisk()
    {
        $icon = new Icon('<path d="M0 0"/>', ['viewBox' => '0 0 24 24']);
        $inner = new InMemoryIconRegistry(['lucide:heart' => $icon]);
        $local = new LocalSvgIconRegistry(new IconFactory(), $this->iconDir);

        $result = new AutoLockIconRegistry($inner, $local)->get('lucide:heart');

        $this->assertSame($icon, $result);
        $this->assertFileExists($this->iconDir.'/lucide/heart.svg');
        $this->assertStringEqualsFile($this->iconDir.'/lucide/heart.svg', $icon->toHtml());
    }

    public function testPropagatesIconNotFoundAndWritesNothing()
    {
        $local = new LocalSvgIconRegistry(new IconFactory(), $this->iconDir);
        $registry = new AutoLockIconRegistry(new InMemoryIconRegistry(), $local);

        try {
            $registry->get('lucide:heart');
            $this->fail('Expected IconNotFoundException.');
        } catch (IconNotFoundException) {
            $this->assertFileDoesNotExist($this->iconDir.'/lucide/heart.svg');
        }
    }

    public function testWriteFailureIsNonFatalAndLogged()
    {
        $icon = new Icon('<path d="M0 0"/>');
        $inner = new InMemoryIconRegistry(['lucide:heart' => $icon]);

        // Point the local registry at a *file* so writing "<file>/lucide/heart.svg" fails.
        $file = tempnam(sys_get_temp_dir(), 'ux_icons_autolock_file_');
        $local = new LocalSvgIconRegistry(new IconFactory(), $file);

        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };

        $result = new AutoLockIconRegistry($inner, $local, $logger)->get('lucide:heart');

        unlink($file);

        $this->assertSame($icon, $result);
        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::WARNING, $logger->records[0]['level']);
    }
}
