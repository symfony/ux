<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\UX\Translator\Dumper\Front\DomainModuleDumper;
use Symfony\UX\Translator\Dumper\Front\TranslationConfigDumper;
use Symfony\UX\Translator\Dumper\Front\MessageConstantDumper;
use Symfony\UX\Translator\TranslationsDumper;

final class TranslationDumperTest extends TestCase
{
    protected static $cacheDir;

    public static function setUpBeforeClass(): void
    {
        self::$cacheDir = tempnam(sys_get_temp_dir(), 'sf_cache_warmer_dir');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$cacheDir);
    }

    public function test()
    {
        $translatorBag = new TranslatorBag();
        $messageObjectTranslationDumper = $this->createMock(MessageConstantDumper::class);
        $messageObjectTranslationDumper
            ->expects($this->once())
            ->method('dump')
            ->with(...$translatorBag->getCatalogues());
        $domainModuleTranslationDumper = $this->createMock(DomainModuleDumper::class);
        $domainModuleTranslationDumper
            ->expects($this->once())
            ->method('dump')
            ->with(...$translatorBag->getCatalogues());
        $frontConfigDumper = $this->createMock(TranslationConfigDumper::class);
        $frontConfigDumper
            ->expects($this->once())
            ->method('dump')
            ->with(...$translatorBag->getCatalogues());

        $mainTranslationDumper = new TranslationsDumper('dump_dir_path');
        $mainTranslationDumper->addDumper($messageObjectTranslationDumper);
        $mainTranslationDumper->addDumper($domainModuleTranslationDumper);
        $mainTranslationDumper->addDumper($frontConfigDumper);
        

        $mainTranslationDumper->dump(...$translatorBag->getCatalogues());
    }
}
