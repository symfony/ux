<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Util\XliffUtils;

class TranslationFilesTest extends TestCase
{
    private const TRANSLATIONS_DIR = __DIR__.'/../../translations';

    #[DataProvider('provideTranslationFiles')]
    public function testTranslationFileIsValid(string $filePath): void
    {
        $document = new \DOMDocument();
        $document->load($filePath);

        $errors = XliffUtils::validateSchema($document);

        $this->assertCount(0, $errors, \sprintf('"%s" is invalid:%s', $filePath, \PHP_EOL.implode(\PHP_EOL, array_column($errors, 'message'))));
    }

    public function testTargetsKeepTheirSourcePlaceholders(): void
    {
        $invalidUnits = [];

        foreach (self::provideTranslationFiles() as [$filePath]) {
            $document = new \DOMDocument();
            $document->load($filePath);

            foreach ($document->getElementsByTagName('trans-unit') as $unit) {
                $source = $unit->getElementsByTagName('source')->item(0)->textContent;
                $target = $unit->getElementsByTagName('target')->item(0)->textContent;

                if (self::getPlaceholders($source) !== self::getPlaceholders($target)) {
                    $invalidUnits[] = basename($filePath).' #'.$unit->getAttribute('id');
                }
            }
        }

        $this->assertSame([], $invalidUnits);
    }

    public static function provideTranslationFiles(): iterable
    {
        foreach (glob(self::TRANSLATIONS_DIR.'/*.xlf') as $filePath) {
            yield basename($filePath) => [$filePath];
        }
    }

    /**
     * @return list<string>
     */
    private static function getPlaceholders(string $message): array
    {
        preg_match_all('/%\w+%/', $message, $matches);
        $placeholders = $matches[0];
        sort($placeholders);

        return $placeholders;
    }
}
