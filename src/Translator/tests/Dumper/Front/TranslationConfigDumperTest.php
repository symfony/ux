<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Dumper\Front;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\UX\Translator\Dumper\Front\TranslationConfigDumper;

class TranslationConfigDumperTest extends TestCase
{
    protected static $translationsDumpDir;
    protected static $fixturesDir = __DIR__.'/../../fixtures';

    public static function setUpBeforeClass(): void
    {
        self::$translationsDumpDir = sys_get_temp_dir().'/sf_ux_translator/'.uniqid('translations', true);
    }

    public static function tearDownAfterClass(): void
    {
        @rmdir(self::$translationsDumpDir);
    }

    public function testDumpNoFallback(): void
    {
        $translationsDumper = new TranslationConfigDumper(
            new Filesystem(),
        );
        $translationsDumper->setDumpDir(self::$translationsDumpDir);

        $translationsDumper->dump(
            new MessageCatalogue('en', include self::$fixturesDir.'/catalogue_en.php'),
            new MessageCatalogue('fr', include self::$fixturesDir.'/catalogue_fr.php')
        );

        $this->assertFileExists(self::$translationsDumpDir.'/configuration.js');
        $this->assertFileExists(self::$translationsDumpDir.'/configuration.d.ts');

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/configuration.js', <<<'JAVASCRIPT'
export const localeFallbacks = {"en":null,"fr":null};

JAVASCRIPT);

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/configuration.d.ts', <<<'TYPESCRIPT'
import { LocaleType } from '@symfony/ux-translator';

export declare const localeFallbacks: Record<LocaleType, LocaleType>;
TYPESCRIPT);
    }

    public function testDumpWithFallback(): void
    {
        $translationsDumper = new TranslationConfigDumper(
            new Filesystem(),
        );
        $translationsDumper->setDumpDir(self::$translationsDumpDir);

        $enCatalogue = new MessageCatalogue('en', include self::$fixturesDir.'/catalogue_en.php');
        $frCatalogue = new MessageCatalogue('fr', include self::$fixturesDir.'/catalogue_fr.php');
        $frCatalogue->addFallbackCatalogue($enCatalogue);
        $translationsDumper->dump($enCatalogue, $frCatalogue);

        $this->assertFileExists(self::$translationsDumpDir.'/configuration.js');
        $this->assertFileExists(self::$translationsDumpDir.'/configuration.d.ts');

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/configuration.js', <<<'JAVASCRIPT'
export const localeFallbacks = {"en":null,"fr":"en"};

JAVASCRIPT);

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/configuration.d.ts', <<<'TYPESCRIPT'
import { LocaleType } from '@symfony/ux-translator';

export declare const localeFallbacks: Record<LocaleType, LocaleType>;
TYPESCRIPT);
    }
}
