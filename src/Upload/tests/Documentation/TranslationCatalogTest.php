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
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class TranslationCatalogTest extends TestCase
{
    public function testCatalogsUseTheBundleDomainAndSemanticKeys()
    {
        $catalogs = glob(\dirname(__DIR__, 2).'/translations/*.xlf');
        self::assertIsArray($catalogs);
        self::assertCount(8, $catalogs);

        foreach ($catalogs as $catalog) {
            self::assertMatchesRegularExpression('/\/UXUploadBundle\.[a-z]{2}\.xlf$/', $catalog);

            $document = new \DOMDocument();
            self::assertTrue($document->load($catalog));

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('x', 'urn:oasis:names:tc:xliff:document:1.2');
            $units = $xpath->query('//x:trans-unit');
            self::assertNotFalse($units);
            self::assertCount(29, $units);

            foreach ($units as $unit) {
                self::assertInstanceOf(\DOMElement::class, $unit);
                $sources = $xpath->query('x:source', $unit);
                self::assertNotFalse($sources);
                $source = $sources->item(0);
                self::assertInstanceOf(\DOMElement::class, $source);
                self::assertStringStartsWith('ux_upload.', $unit->getAttribute('resname'));
                self::assertSame($unit->getAttribute('resname'), $source->textContent);
            }
        }
    }
}
