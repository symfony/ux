<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Registry\DependenciesResolver;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItem;
use Symfony\UX\Toolkit\Registry\RegistryItemType;

/**
 * @author Jean-François Lépine
 *
 * @group circular-reference
 */
class DependenciesResolverTest extends TestCase
{
    public function testItShouldResolveDependenciesOrder(): void
    {
        $registry = new Registry();

        $registry->add(
            new RegistryItem(
                'cell',
                RegistryItemType::Component,
                'default',
                'row',
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'table',
                RegistryItemType::Component,
                'default',
                null,
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'row',
                RegistryItemType::Component,
                'default',
                'table',
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'button',
                RegistryItemType::Component,
                'default',
                null,
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'icon',
                RegistryItemType::Component,
                'default',
                'button',
                ''
            )
        );

        $resolver = new DependenciesResolver();
        $resolved = $resolver->resolve($registry);

        $this->assertEquals('table', $resolved[0]->name);
        $this->assertEquals('row', $resolved[1]->name);
        $this->assertEquals('cell', $resolved[2]->name);
        $this->assertEquals('button', $resolved[3]->name);
        $this->assertEquals('icon', $resolved[4]->name);
    }

    public function testCircularDependency(): void
    {
        $registry = new Registry();

        $registry->add(
            new RegistryItem(
                'cell',
                RegistryItemType::Component,
                'default',
                'row',
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'table',
                RegistryItemType::Component,
                'default',
                'cell',
                ''
            )
        );

        $registry->add(
            new RegistryItem(
                'row',
                RegistryItemType::Component,
                'default',
                'table',
                ''
            )
        );

        $resolver = new DependenciesResolver();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected: table -> cell');
        $resolver->resolve($registry);
    }
}
