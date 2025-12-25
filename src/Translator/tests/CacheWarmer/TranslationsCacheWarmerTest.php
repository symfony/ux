<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\CacheWarmer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\UX\Translator\CacheWarmer\TranslationsCacheWarmer;
use Symfony\UX\Translator\TranslationsDumper;

final class TranslationsCacheWarmerTest extends TestCase
{
    protected static $cacheDir;

    public static function setUpBeforeClass(): void
    {
        self::$cacheDir = tempnam(sys_get_temp_dir(), 'sf_cache_warmer_dir');
    }

    public static function tearDownAfterClass(): void
    {
        @rmdir(self::$cacheDir);
    }

    public function test()
    {
        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(
            new MessageCatalogue('en', [
                'messages' => [
                    'foo' => 'bar',
                ],
            ])
        );

        $dumpDir = '/tmp/translations';
        $dumpTypeScript = true;
        $includedDomains = [];
        $excludedDomains = [];

        $translationsDumperMock = $this->createMock(TranslationsDumper::class);
        $translationsDumperMock
            ->expects($this->once())
            ->method('dump')
            ->with($translatorBag->getCatalogues(), $dumpDir, $dumpTypeScript, $includedDomains, $excludedDomains);

        $translationsCacheWarmer = new TranslationsCacheWarmer(
            $translatorBag,
            $translationsDumperMock,
            $dumpDir,
            $dumpTypeScript,
            $includedDomains,
            $excludedDomains
        );

        $translationsCacheWarmer->warmUp(self::$cacheDir);
    }
}
