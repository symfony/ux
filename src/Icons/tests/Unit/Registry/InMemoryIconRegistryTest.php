<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Registry\InMemoryIconRegistry;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class InMemoryIconRegistryTest extends TestCase
{
    public function testRegistryConstructor(): void
    {
        $icon = new Icon('foo', ['bar' => 'foobar']);
        $registry = new InMemoryIconRegistry(['foo' => $icon]);

        $this->assertSame($icon, $registry->get('foo'));
    }

    public function testRegistryReplaceIcon(): void
    {
        $registry = new InMemoryIconRegistry();
        $foo = new Icon('foo', []);
        $bar = new Icon('bar', []);

        $registry->set('foo', $foo);
        $this->assertSame($foo, $registry->get('foo'));

        $registry->set('bar', $bar);
        $this->assertSame($bar, $registry->get('bar'));

        $registry->set('bar', $foo);
        $this->assertSame($foo, $registry->get('bar'));
    }

    public function testRegistryThrowsExceptionOnUnknownIcon(): void
    {
        $this->expectException(IconNotFoundException::class);
        $this->expectExceptionMessage('Icon "foo" not found.');

        $registry = new InMemoryIconRegistry();
        $registry->get('foo');
    }
}
