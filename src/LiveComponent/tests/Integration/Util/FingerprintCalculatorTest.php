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
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\create;

final class FingerprintCalculatorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testFingerprintEqual()
    {
        $fingerprintCalculator = self::getContainer()->get('ux.live_component.fingerprint_calculator');

        $entityOne = create(Entity1::class)->object();

        $entityTwo = clone $entityOne;

        self::assertNotSame(
            spl_object_id($entityOne),
            spl_object_id($entityTwo)
        );

        self::assertSame(
            $fingerprintCalculator->calculateFingerprint([$entityOne]),
            $fingerprintCalculator->calculateFingerprint([$entityTwo])
        );
    }

    public function testFingerprintNotEqual()
    {
        $fingerprintCalculator = self::getContainer()->get('ux.live_component.fingerprint_calculator');

        $entityOne = create(Entity1::class)->object();

        $entityTwo = create(Entity1::class)->object();

        self::assertNotSame(
            $fingerprintCalculator->calculateFingerprint([$entityOne]),
            $fingerprintCalculator->calculateFingerprint([$entityTwo])
        );
    }
}
