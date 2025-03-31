<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Kit\Kit;

final class KitTest extends TestCase
{
    public function testShouldFailIfKitNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid kit name "-foobar".');

        new Kit(__DIR__, '-foobar', 'https://example.com', [], 'MIT');
    }

    public function testShouldFailIfKitPathIsNotAbsolute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Kit path "./%s" is not absolute.', __DIR__));

        new Kit(\sprintf('./%s', __DIR__), 'foo', 'https://example.com', [], 'MIT');
    }

    public function testCanAddComponentsToTheKit(): void
    {
        $kit = new Kit(__DIR__, 'foo', 'https://example.com', [], 'MIT');
        $kit->addComponent(new Component('Table', [new File(FileType::Twig, 'Table.html.twig', 'Table.html.twig')], null));
        $kit->addComponent(new Component('Table:Row', [new File(FileType::Twig, 'Table/Row.html.twig', 'Table/Row.html.twig')], null));

        $this->assertCount(2, $kit->getComponents());
    }

    public function testShouldFailIfComponentIsAlreadyRegisteredInTheKit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Component "Table" is already registered in the kit.');

        $kit = new Kit(__DIR__, 'foo', 'https://example.com', [], 'MIT');
        $kit->addComponent(new Component('Table', [new File(FileType::Twig, 'Table.html.twig', 'Table.html.twig')], null));
        $kit->addComponent(new Component('Table', [new File(FileType::Twig, 'Table.html.twig', 'Table.html.twig')], null));
    }

    public function testCanGetComponentByName(): void
    {
        $kit = new Kit(__DIR__, 'foo', 'https://example.com', [], 'MIT');
        $kit->addComponent(new Component('Table', [new File(FileType::Twig, 'Table.html.twig', 'Table.html.twig')], null));
        $kit->addComponent(new Component('Table:Row', [new File(FileType::Twig, 'Table/Row.html.twig', 'Table/Row.html.twig')], null));

        $this->assertSame('Table', $kit->getComponent('Table')->name);
        $this->assertSame('Table:Row', $kit->getComponent('Table:Row')->name);
    }

    public function testShouldReturnNullIfComponentIsNotFound(): void
    {
        $kit = new Kit(__DIR__, 'foo', 'https://example.com', [], 'MIT');

        $this->assertNull($kit->getComponent('Table:Cell'));
    }
}
