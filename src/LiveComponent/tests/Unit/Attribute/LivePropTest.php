<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LivePropTest extends TestCase
{
    public function testHydrateWithMethod(): void
    {
        $this->assertSame('someMethod', (new LiveProp(false, 'someMethod'))->hydrateMethod());
        $this->assertSame('someMethod', (new LiveProp(false, 'someMethod()'))->hydrateMethod());
    }

    public function testDehydrateWithMethod(): void
    {
        $this->assertSame('someMethod', (new LiveProp(false, null, 'someMethod'))->dehydrateMethod());
        $this->assertSame('someMethod', (new LiveProp(false, null, 'someMethod()'))->dehydrateMethod());
    }

    public function testCanCallCalculateFieldNameAsString(): void
    {
        $component = new class() {};

        $this->assertSame('field', (new LiveProp(false, null, null, 'field'))->calculateFieldName($component, 'fallback'));
    }

    public function testCanCallCalculateFieldNameAsMethod(): void
    {
        $component = new class() {
            public function fieldName(): string
            {
                return 'foo';
            }
        };

        $this->assertSame('foo', (new LiveProp(false, null, null, 'fieldName()'))->calculateFieldName($component, 'fallback'));
    }

    public function testCanCallCalculateFieldNameWhenNotSet(): void
    {
        $component = new class() {};

        $this->assertSame('fallback', (new LiveProp())->calculateFieldName($component, 'fallback'));
    }

    public function testIsIdentityWritableAndWritablePaths()
    {
        $liveProp = new LiveProp(true);
        $this->assertTrue($liveProp->isIdentityWritable());
        $this->assertEmpty($liveProp->writablePaths());

        $liveProp2 = new LiveProp([LiveProp::IDENTITY, 'bar']);
        $this->assertTrue($liveProp2->isIdentityWritable());
        $this->assertSame(['bar'], $liveProp2->writablePaths());

        $liveProp3 = new LiveProp(['bar']);
        $this->assertFalse($liveProp3->isIdentityWritable());
        $this->assertSame(['bar'], $liveProp3->writablePaths());
    }
}
