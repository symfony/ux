<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Documentation;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class TranslationCatalogTest extends TestCase
{
    private const REFERENCE_CATALOG = 'UXUploadBundle.en.xlf';

    /**
     * @return iterable<string, array{string}>
     */
    public static function catalogs(): iterable
    {
        foreach (glob(self::translationsDir().'/*.xlf') ?: [] as $catalog) {
            yield basename($catalog) => [$catalog];
        }
    }

    #[DataProvider('catalogs')]
    public function testCatalogUsesTheBundleDomainAndTheReferenceKeys(string $catalog): void
    {
        self::assertMatchesRegularExpression('/\/UXUploadBundle\.[a-z]{2}\.xlf$/', $catalog);

        $reference = $this->messages(self::translationsDir().'/'.self::REFERENCE_CATALOG);
        $messages = $this->messages($catalog);

        self::assertSame(array_keys($reference), array_keys($messages));
        foreach ($messages as $key => $target) {
            self::assertStringStartsWith('ux_upload.', $key);
            self::assertNotSame('', $target, $key);
        }
    }

    /**
     * @return array<string, string>
     */
    private function messages(string $catalog): array
    {
        $document = new \DOMDocument();
        self::assertTrue($document->load($catalog));

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('x', 'urn:oasis:names:tc:xliff:document:1.2');
        $units = $xpath->query('//x:trans-unit');
        self::assertNotFalse($units);

        $messages = [];
        foreach ($units as $unit) {
            self::assertInstanceOf(\DOMElement::class, $unit);
            $source = trim((string) $xpath->evaluate('string(x:source)', $unit));
            self::assertArrayNotHasKey($source, $messages, \sprintf('Duplicate key "%s" in %s.', $source, basename($catalog)));
            $messages[$source] = trim((string) $xpath->evaluate('string(x:target)', $unit));
        }
        self::assertNotSame([], $messages);
        ksort($messages);

        return $messages;
    }

    private static function translationsDir(): string
    {
        return \dirname(__DIR__, 2).'/translations';
    }
}
