<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Util;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

final class FingerprintCalculatorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testFingerprintEqual()
    {
        $fingerprintCalculator = $this->getFingerprintCalculator();
        $entityOne = persist(Entity1::class);

        $entityTwo = clone $entityOne;

        self::assertNotSame(
            spl_object_id($entityOne),
            spl_object_id($entityTwo)
        );

        $metadata1 = $this->createMock(LiveComponentMetadata::class);
        $metadata1
            ->expects($this->once())
            ->method('getOnlyPropsThatAcceptUpdatesFromParent')
            ->with([$entityOne])
            ->willReturn([$entityOne]);

        $metadata2 = $this->createMock(LiveComponentMetadata::class);
        $metadata2
            ->expects($this->once())
            ->method('getOnlyPropsThatAcceptUpdatesFromParent')
            ->with([$entityTwo])
            ->willReturn([$entityTwo]);

        self::assertSame(
            $fingerprintCalculator->calculateFingerprint([$entityOne], $metadata1),
            $fingerprintCalculator->calculateFingerprint([$entityTwo], $metadata2)
        );
    }

    public function testFingerprintNotEqual()
    {
        $fingerprintCalculator = $this->getFingerprintCalculator();

        $entityOne = persist(Entity1::class);

        $entityTwo = persist(Entity1::class);

        $metadata1 = $this->createMock(LiveComponentMetadata::class);
        $metadata1
            ->expects($this->once())
            ->method('getOnlyPropsThatAcceptUpdatesFromParent')
            ->with([$entityOne])
            ->willReturn([$entityOne]);

        $metadata2 = $this->createMock(LiveComponentMetadata::class);
        $metadata2
            ->expects($this->once())
            ->method('getOnlyPropsThatAcceptUpdatesFromParent')
            ->with([$entityTwo])
            ->willReturn([$entityTwo]);

        self::assertNotSame(
            $fingerprintCalculator->calculateFingerprint([$entityOne], $metadata1),
            $fingerprintCalculator->calculateFingerprint([$entityTwo], $metadata2)
        );
    }

    public function testFingerprintOnlyUsesPropsThatAcceptUpdates(): void
    {
        $fingerprintCalculator = $this->getFingerprintCalculator();

        $inputProps = ['foo' => 'fooValue', 'bar' => 'barValue'];

        $metadata = $this->createMock(LiveComponentMetadata::class);
        $metadata
            ->expects($this->once())
            ->method('getOnlyPropsThatAcceptUpdatesFromParent')
            ->with($inputProps)
            ->willReturn(['foo' => 'fooValue']);

        self::assertSame(
            'SRfLHaj7eA9qpiz5/qR32jTsdsmbDit4ejkMigdisGY=',
            $fingerprintCalculator->calculateFingerprint($inputProps, $metadata),
        );
    }

    private function getFingerprintCalculator(): FingerprintCalculator
    {
        return self::getContainer()->get('ux.live_component.fingerprint_calculator');
    }
}
