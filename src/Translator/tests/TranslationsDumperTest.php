<?php

namespace Symfony\UX\Translator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\UX\Translator\MessageParameters\Extractor\IntlMessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Extractor\MessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Printer\TypeScriptMessageParametersPrinter;
use Symfony\UX\Translator\TranslationsDumper;

class TranslationsDumperTest extends TestCase
{
    protected static $translationsDumpDir;

    public static function setUpBeforeClass(): void
    {
        self::$translationsDumpDir = sys_get_temp_dir().'/sf_ux_translator/'.uniqid('translations', true);
    }

    public static function tearDownAfterClass(): void
    {
        @rmdir(self::$translationsDumpDir);
    }

    public function testDump(): void
    {
        $translationsDumper = new TranslationsDumper(
            self::$translationsDumpDir,
            new MessageParametersExtractor(),
            new IntlMessageParametersExtractor(),
            new TypeScriptMessageParametersPrinter(),
            new Filesystem(),
        );

        $translationsDumper->dump(
            new MessageCatalogue('en', [
                'messages+intl-icu' => [
                    'notification.comment_created' => 'Your post received a comment!',
                    'notification.comment_created.description' => 'Your post "{title}" has received a new comment. You can read the comment by following <a href="{link}">this link</a>',
                    'post.num_comments' => '{count, plural, one {# comment} other {# comments}}',
                ],
                'messages' => [
                    'symfony.great' => 'Symfony is awesome!',
                    'symfony.what' => 'Symfony is %what%!',
                    'apples.count.0' => 'There is 1 apple|There are %count% apples',
                    'apples.count.1' => '{1} There is one apple|]1,Inf] There are %count% apples',
                    'apples.count.2' => '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                    'apples.count.3' => 'one: There is one apple|more: There are %count% apples',
                    'apples.count.4' => 'one: There is one apple|more: There are more than one apple',
                    'what.count.1' => '{1} There is one %what%|]1,Inf] There are %count% %what%',
                    'what.count.2' => '{0} There are no %what%|{1} There is one %what%|]1,Inf] There are %count% %what%',
                    'what.count.3' => 'one: There is one %what%|more: There are %count% %what%',
                    'what.count.4' => 'one: There is one %what%|more: There are more than one %what%',
                ],
                'foobar' => [
                    'post.num_comments' => 'There is 1 comment|There are %count% comments',
                ],
            ]),
            new MessageCatalogue('fr', [
                'messages+intl-icu' => [
                    'notification.comment_created' => 'Votre article a reçu un commentaire !',
                    'notification.comment_created.description' => 'Votre article "{title}" a reçu un nouveau commentaire. Vous pouvez lire le commentaire en suivant <a href="{link}">ce lien</a>',
                    'post.num_comments' => '{count, plural, one {# commentaire} other {# commentaires}}',
                ],
                'messages' => [
                    'symfony.great' => 'Symfony est génial !',
                    'symfony.what' => 'Symfony est %what%!',
                    'apples.count.0' => 'Il y a 1 pomme|Il y a %count% pommes',
                    'apples.count.1' => '{1} Il y a une pomme|]1,Inf] Il y a %count% pommes',
                    'apples.count.2' => '{0} Il n\'y a pas de pommes|{1} Il y a une pomme|]1,Inf] Il y a %count% pommes',
                    'apples.count.3' => 'one: Il y a une pomme|more: Il y a %count% pommes',
                    'apples.count.4' => 'one: Il y a une pomme|more: Il y a plus d\'une pomme',
                    'what.count.1' => '{1} Il y a une %what%|]1,Inf] Il y a %count% %what%',
                    'what.count.2' => '{0} Il n\'y a pas de %what%|{1} Il y a une %what%|]1,Inf] Il y a %count% %what%',
                    'what.count.3' => 'one: Il y a une %what%|more: Il y a %count% %what%',
                    'what.count.4' => 'one: Il y a une %what%|more: Il y a more than one %what%',
                ],
                'foobar' => [
                    'post.num_comments' => 'Il y a 1 comment|Il y a %count% comments',
                ],
            ])
        );

        $this->assertFileExists(self::$translationsDumpDir.'/index.js');
        $this->assertFileExists(self::$translationsDumpDir.'/index.d.ts');

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/index.js', <<<'JAVASCRIPT'
export const NOTIFICATION_COMMENT_CREATED = {"id":"notification.comment_created","translations":{"messages+intl-icu":{"en":"Your post received a comment!","fr":"Votre article a re\u00e7u un commentaire !"}}};
export const NOTIFICATION_COMMENT_CREATED_DESCRIPTION = {"id":"notification.comment_created.description","translations":{"messages+intl-icu":{"en":"Your post \"{title}\" has received a new comment. You can read the comment by following <a href=\"{link}\">this link<\/a>","fr":"Votre article \"{title}\" a re\u00e7u un nouveau commentaire. Vous pouvez lire le commentaire en suivant <a href=\"{link}\">ce lien<\/a>"}}};
export const POST_NUM_COMMENTS = {"id":"post.num_comments","translations":{"messages+intl-icu":{"en":"{count, plural, one {# comment} other {# comments}}","fr":"{count, plural, one {# commentaire} other {# commentaires}}"},"foobar":{"en":"There is 1 comment|There are %count% comments","fr":"Il y a 1 comment|Il y a %count% comments"}}};
export const SYMFONY_GREAT = {"id":"symfony.great","translations":{"messages":{"en":"Symfony is awesome!","fr":"Symfony est g\u00e9nial !"}}};
export const SYMFONY_WHAT = {"id":"symfony.what","translations":{"messages":{"en":"Symfony is %what%!","fr":"Symfony est %what%!"}}};
export const APPLES_COUNT0 = {"id":"apples.count.0","translations":{"messages":{"en":"There is 1 apple|There are %count% apples","fr":"Il y a 1 pomme|Il y a %count% pommes"}}};
export const APPLES_COUNT1 = {"id":"apples.count.1","translations":{"messages":{"en":"{1} There is one apple|]1,Inf] There are %count% apples","fr":"{1} Il y a une pomme|]1,Inf] Il y a %count% pommes"}}};
export const APPLES_COUNT2 = {"id":"apples.count.2","translations":{"messages":{"en":"{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples","fr":"{0} Il n'y a pas de pommes|{1} Il y a une pomme|]1,Inf] Il y a %count% pommes"}}};
export const APPLES_COUNT3 = {"id":"apples.count.3","translations":{"messages":{"en":"one: There is one apple|more: There are %count% apples","fr":"one: Il y a une pomme|more: Il y a %count% pommes"}}};
export const APPLES_COUNT4 = {"id":"apples.count.4","translations":{"messages":{"en":"one: There is one apple|more: There are more than one apple","fr":"one: Il y a une pomme|more: Il y a plus d'une pomme"}}};
export const WHAT_COUNT1 = {"id":"what.count.1","translations":{"messages":{"en":"{1} There is one %what%|]1,Inf] There are %count% %what%","fr":"{1} Il y a une %what%|]1,Inf] Il y a %count% %what%"}}};
export const WHAT_COUNT2 = {"id":"what.count.2","translations":{"messages":{"en":"{0} There are no %what%|{1} There is one %what%|]1,Inf] There are %count% %what%","fr":"{0} Il n'y a pas de %what%|{1} Il y a une %what%|]1,Inf] Il y a %count% %what%"}}};
export const WHAT_COUNT3 = {"id":"what.count.3","translations":{"messages":{"en":"one: There is one %what%|more: There are %count% %what%","fr":"one: Il y a une %what%|more: Il y a %count% %what%"}}};
export const WHAT_COUNT4 = {"id":"what.count.4","translations":{"messages":{"en":"one: There is one %what%|more: There are more than one %what%","fr":"one: Il y a une %what%|more: Il y a more than one %what%"}}};

JAVASCRIPT);

        $this->assertStringEqualsFile(self::$translationsDumpDir.'/index.d.ts', <<<'TYPESCRIPT'
import { Message, NoParametersType } from '@symfony/ux-translator';

export declare const NOTIFICATION_COMMENT_CREATED: Message<{ 'messages+intl-icu': { parameters: NoParametersType } }, 'en'|'fr'>;
export declare const NOTIFICATION_COMMENT_CREATED_DESCRIPTION: Message<{ 'messages+intl-icu': { parameters: { 'title': string, 'link': string } } }, 'en'|'fr'>;
export declare const POST_NUM_COMMENTS: Message<{ 'messages+intl-icu': { parameters: { 'count': number } }, 'foobar': { parameters: { '%count%': number } } }, 'en'|'fr'>;
export declare const SYMFONY_GREAT: Message<{ 'messages': { parameters: NoParametersType } }, 'en'|'fr'>;
export declare const SYMFONY_WHAT: Message<{ 'messages': { parameters: { '%what%': string } } }, 'en'|'fr'>;
export declare const APPLES_COUNT0: Message<{ 'messages': { parameters: { '%count%': number } } }, 'en'|'fr'>;
export declare const APPLES_COUNT1: Message<{ 'messages': { parameters: { '%count%': number } } }, 'en'|'fr'>;
export declare const APPLES_COUNT2: Message<{ 'messages': { parameters: { '%count%': number } } }, 'en'|'fr'>;
export declare const APPLES_COUNT3: Message<{ 'messages': { parameters: { '%count%': number } } }, 'en'|'fr'>;
export declare const APPLES_COUNT4: Message<{ 'messages': { parameters: NoParametersType } }, 'en'|'fr'>;
export declare const WHAT_COUNT1: Message<{ 'messages': { parameters: { '%what%': string, '%count%': number } } }, 'en'|'fr'>;
export declare const WHAT_COUNT2: Message<{ 'messages': { parameters: { '%what%': string, '%count%': number } } }, 'en'|'fr'>;
export declare const WHAT_COUNT3: Message<{ 'messages': { parameters: { '%what%': string, '%count%': number } } }, 'en'|'fr'>;
export declare const WHAT_COUNT4: Message<{ 'messages': { parameters: { '%what%': string } } }, 'en'|'fr'>;

TYPESCRIPT);
    }
}
