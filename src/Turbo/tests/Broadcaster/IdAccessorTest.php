<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Broadcaster;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Membership;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Player;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Team;
use Symfony\UX\Turbo\Tests\Fixtures\EntityManagerFactory;

class IdAccessorTest extends TestCase
{
    public function testGetEntityIdReturnsTheIdentifierValue(): void
    {
        $this->assertSame(['id' => 42], $this->createIdAccessor()->getEntityId(new Player(42)));
    }

    public function testGetEntityIdResolvesAnAssociationToItsOwnIdentifier(): void
    {
        $membership = new Membership(new Player(1), new Team(2));

        $this->assertSame(['player' => '1', 'team' => '2'], $this->createIdAccessor()->getEntityId($membership));
    }

    private function createIdAccessor(): IdAccessor
    {
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManagerForClass')->willReturn(EntityManagerFactory::create());

        return new IdAccessor(doctrine: $doctrine);
    }
}
