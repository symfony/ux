<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Dumper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Dumper\JsonFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\UX\Translator\Dumper\ModuleDumper;

class ModuleDumperTest extends TestCase
{
    protected static string $translationsDumpDir;
    protected static string $fixturesDir = __DIR__.'/../fixtures';
    private ModuleDumper $translationsDumper;

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
        $this->translationsDumper = new ModuleDumper(new JsonFileDumper());
    }

    public function testDump(): void
    {
        $this->translationsDumper->dump(
            new MessageCatalogue('en', include self::$fixturesDir.'/catalogue_en.php'),
            ['path' => self::$translationsDumpDir.'/domains/translations']
        );

        $this->assertFileExists(self::$translationsDumpDir.'/domains/translations/messages.en.js');
        $this->assertFileExists(self::$translationsDumpDir.'/domains/translations/messages+intl-icu.en.js');
        $this->assertFileExists(self::$translationsDumpDir.'/domains/translations/foobar.en.js');

        $this->assertStringContainsString(<<<'JS'
export default {
    "symfony.great": "Symfony is awesome!",
    "symfony.what": "Symfony is %what%!",
    "symfony.what!": "Symfony is %what%! (should not conflict with the previous one.)",
    "symfony.what.": "Symfony is %what%. (should also not conflict with the previous one.)",
    "apples.count.0": "There is 1 apple|There are %count% apples",
    "apples.count.1": "{1} There is one apple|]1,Inf] There are %count% apples",
    "apples.count.2": "{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples",
    "apples.count.3": "one: There is one apple|more: There are %count% apples",
    "apples.count.4": "one: There is one apple|more: There are more than one apple",
    "what.count.1": "{1} There is one %what%|]1,Inf] There are %count% %what%",
    "what.count.2": "{0} There are no %what%|{1} There is one %what%|]1,Inf] There are %count% %what%",
    "what.count.3": "one: There is one %what%|more: There are %count% %what%",
    "what.count.4": "one: There is one %what%|more: There are more than one %what%",
    "animal.dog-cat": "Dog and cat",
    "animal.dog_cat": "Dog and cat (should not conflict with the previous one)",
    "0starts.with.numeric": "Key starts with numeric char"
};
JS, file_get_contents(self::$translationsDumpDir.'/domains/translations/messages.en.js'));


        $this->assertStringContainsString(<<<'JS'
export default {
    "notification.comment_created": "Your post received a comment!",
    "notification.comment_created.description": "Your post \"{title}\" has received a new comment. You can read the comment by following <a href=\"{link}\">this link<\/a>",
    "post.num_comments": "{count, plural, one {# comment} other {# comments}}",
    "post.num_comments.": "{count, plural, one {# comment} other {# comments}} (should not conflict with the previous one.)"
};
JS, file_get_contents(self::$translationsDumpDir.'/domains/translations/messages+intl-icu.en.js'));

        $this->assertStringContainsString(<<<'JS'
export default {
    "post.num_comments": "There is 1 comment|There are %count% comments"
};
JS, file_get_contents(self::$translationsDumpDir.'/domains/translations/foobar.en.js'));
    }
}
