<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Turbo\Doctrine\BroadcastListener;
use Symfony\UX\Turbo\Tests\Fixtures\CollectingBroadcaster;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Membership;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Player;
use Symfony\UX\Turbo\Tests\Fixtures\Entity\Team;
use Symfony\UX\Turbo\Tests\Fixtures\EntityManagerFactory;

class BroadcastListenerTest extends TestCase
{
    public function testBroadcastACreatedEntityWhoseIdentifierIsMadeOfAssociations(): void
    {
        $entityManager = EntityManagerFactory::create();
        $broadcaster = $this->listenTo($entityManager);

        $entityManager->persist($player = new Player(1));
        $entityManager->persist($team = new Team(2));
        $entityManager->persist(new Membership($player, $team));
        $entityManager->flush();

        $this->assertSame([['create', ['id' => ['player' => '1', 'team' => '2']]]], $broadcaster->broadcasts);
    }

    public function testBroadcastAnUpdatedEntityWhoseIdentifierIsMadeOfAssociations(): void
    {
        $entityManager = EntityManagerFactory::create();

        $entityManager->persist($player = new Player(1));
        $entityManager->persist($team = new Team(2));
        $entityManager->persist(new Membership($player, $team));
        $entityManager->flush();
        $entityManager->clear();

        // Reloading makes the identifier associations lazy, a state the create path never reaches.
        $membership = $entityManager->find(Membership::class, ['player' => 1, 'team' => 2]);
        $this->assertNotNull($membership);

        $broadcaster = $this->listenTo($entityManager);

        $membership->role = 'captain';
        $entityManager->flush();

        $this->assertSame([['update', ['id' => ['player' => '1', 'team' => '2']]]], $broadcaster->broadcasts);
    }

    private function listenTo(EntityManager $entityManager): CollectingBroadcaster
    {
        $broadcaster = new CollectingBroadcaster();

        $entityManager->getEventManager()->addEventListener(
            [Events::onFlush, Events::postFlush],
            new BroadcastListener($broadcaster),
        );

        return $broadcaster;
    }
}
