<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Form\DataTransformer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class EditorContentTransformerInterfaceTest extends TestCase
{
    public function testEnumCases(): void
    {
        self::assertSame('scalar', StorageShape::Scalar->value);
        self::assertSame('json', StorageShape::Json->value);
        self::assertSame('split', StorageShape::Split->value);
    }

    public function testContract(): void
    {
        $t = new class implements EditorContentTransformerInterface {
            public function getBridgeId(): string
            {
                return 'fake';
            }

            public function getContentClass(): string
            {
                return HtmlContent::class;
            }

            public function getStorageShape(): StorageShape
            {
                return StorageShape::Scalar;
            }

            public function transform(?EditorContentInterface $c): mixed
            {
                return $c?->getRaw();
            }

            public function reverseTransform(mixed $v): ?EditorContentInterface
            {
                return null === $v ? null : new HtmlContent((string) $v);
            }
        };
        self::assertSame('fake', $t->getBridgeId());
        self::assertSame(HtmlContent::class, $t->getContentClass());
        self::assertSame(StorageShape::Scalar, $t->getStorageShape());
        self::assertSame('hi', $t->transform(new HtmlContent('hi')));
        self::assertNull($t->transform(null));
        self::assertInstanceOf(HtmlContent::class, $t->reverseTransform('hi'));
    }
}
