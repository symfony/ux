<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Content\Converter;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\Converter\ContentConverterInterface;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\Content\EditorContentInterface;

final class ContentConverterRegistryPairsTest extends TestCase
{
    public function testPairsListsRegistered()
    {
        $conv = new class implements ContentConverterInterface {
            public function getFrom(): string
            {
                return 'a';
            }

            public function getTo(): string
            {
                return 'b';
            }

            public function convert(EditorContentInterface $c): EditorContentInterface
            {
                return $c;
            }
        };
        self::assertSame([['from' => 'a', 'to' => 'b']], new ContentConverterRegistry([$conv])->pairs());
        self::assertSame([], new ContentConverterRegistry([])->pairs());
    }
}
