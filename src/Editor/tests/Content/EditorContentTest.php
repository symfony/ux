<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Content;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Content\EditorContentFormat;
use Symfony\UX\Editor\Content\EditorContentInterface;

final class EditorContentTest extends TestCase
{
    public function testAbstractCarriesFormatAndMetadata(): void
    {
        $stub = new class('hi', ['bridgeId' => 'fake']) extends EditorContent {
            public function __construct(public readonly string $raw, array $meta) {
                parent::__construct(EditorContentFormat::Html, $meta);
            }
            public function getRaw(): string { return $this->raw; }
            public function isEmpty(): bool  { return $this->raw === ''; }
        };
        self::assertInstanceOf(EditorContentInterface::class, $stub);
        self::assertSame(EditorContentFormat::Html, $stub->getFormat());
        self::assertSame(['bridgeId' => 'fake'], $stub->getMetadata());
        self::assertSame('hi', $stub->getRaw());
        self::assertFalse($stub->isEmpty());
    }
}
