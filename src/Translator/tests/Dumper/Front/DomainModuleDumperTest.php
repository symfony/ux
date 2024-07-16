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
use Symfony\UX\Translator\Dumper\Front\DomainModuleDumper;

/**
 * @uses \Symfony\UX\Translator\Dumper\ModuleDumper
 */
class DomainModuleDumperTest extends TestCase
{
    protected static $translationsDumpDir;
    protected static $fixturesDir = __DIR__.'/../../fixtures';
    private DomainModuleDumper $translationsDumper;

    public static function setUpBeforeClass(): void
    {
        self::$translationsDumpDir = sys_get_temp_dir().'/sf_ux_translator/'.uniqid('translations', true);
    }

    public static function tearDownAfterClass(): void
    {
        @rmdir(self::$translationsDumpDir);
    }

    protected function setUp(): void
    {
        $this->translationsDumper = new DomainModuleDumper(
            new Filesystem(),
        );
        $this->translationsDumper->setDumpDir(self::$translationsDumpDir);
    }

    public function testDump(): void
    {
        $this->translationsDumper->dump(...$this->getMessageCatalogues());

        $this->assertFileExists(self::$translationsDumpDir.'/domains/messages.js');
        $this->assertFileExists(self::$translationsDumpDir.'/domains/foobar.js');

        $this->assertStringContainsString(<<<'JS'
import messages_fr from './translations/messages.fr.js';
import messages_en from './translations/messages.en.js';
import messages_intl_icu_fr from './translations/messages+intl-icu.fr.js';
import messages_intl_icu_en from './translations/messages+intl-icu.en.js';

export default {
    'messages': {
         'fr': messages_fr,
         'en': messages_en,
    },
    'messages+intl-icu': {
         'fr': messages_intl_icu_fr,
         'en': messages_intl_icu_en,
    },
};
JS, file_get_contents(self::$translationsDumpDir.'/domains/messages.js'));

        $this->assertStringContainsString(<<<'JS'
            import foobar_en from './translations/foobar.en.js';
            import foobar_fr from './translations/foobar.fr.js';

            export default {
                'foobar': {
                     'en': foobar_en,
                     'fr': foobar_fr,
                },
            };
            JS, file_get_contents(self::$translationsDumpDir.'/domains/foobar.js'));
    }

    /**
     * @return list<MessageCatalogue>
     */
    private function getMessageCatalogues(): array
    {
        return [
            new MessageCatalogue('en', include self::$fixturesDir.'/catalogue_en.php'),
            new MessageCatalogue('fr', include self::$fixturesDir.'/catalogue_fr.php'),
        ];
    }
}
