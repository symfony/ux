<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Translator\Tests\Kernel\FrameworkAppKernel;

class DumpEnabledLocalesTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return FrameworkAppKernel::class;
    }

    public function testShouldDumpOnlyEnabledLocales(): void
    {
        self::assertFileExists(__DIR__.'/../Fixtures/translations/messages.en.yaml');
        self::assertFileExists(__DIR__.'/../Fixtures/translations/messages.fr.yaml');
        self::assertFileExists(__DIR__.'/../Fixtures/translations/messages.es.yaml');

        $enabledLocales = self::getContainer()->getParameter('kernel.enabled_locales');
        self::assertNotContains('es', $enabledLocales, 'The "es" locale should not be enabled in this test');

        $translationsDumpDir = self::getContainer()->getParameter('kernel.project_dir').'/var/translations';
        self::assertDirectoryExists($translationsDumpDir);

        self::assertStringEqualsFile(
            $translationsDumpDir.'/index.js',
            <<<JAVASCRIPT
            export const SYMFONY_UX_GREAT = {"id":"symfony_ux.great","translations":{"messages":{"en":"Symfony UX is awesome","fr":"Symfony UX est g\u00e9nial"}}};

            JAVASCRIPT
        );
        self::assertStringEqualsFile(
            $translationsDumpDir.'/index.d.ts',
            <<<TYPESCRIPT
            import { Message, NoParametersType } from '@symfony/ux-translator';

            export declare const SYMFONY_UX_GREAT: Message<{ 'messages': { parameters: NoParametersType } }, 'en'|'fr'>;

            TYPESCRIPT
        );
        self::assertStringEqualsFile(
            $translationsDumpDir.'/configuration.js',
            <<<JAVASCRIPT
            export const localeFallbacks = {"en":null,"fr":"en"};

            JAVASCRIPT
        );
        self::assertStringEqualsFile(
            $translationsDumpDir.'/configuration.d.ts',
            <<<TYPESCRIPT
            import { LocaleType } from '@symfony/ux-translator';

            export declare const localeFallbacks: Record<LocaleType, LocaleType>;
            TYPESCRIPT
        );
    }
}
