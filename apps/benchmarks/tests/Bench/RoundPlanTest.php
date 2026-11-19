<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\RoundPlan;
use App\Bench\Side;
use App\Bench\TargetProvider;
use App\Bench\VendorVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoundPlanTest extends TestCase
{
    public function testSidesRotateWithTheRoundAndTargetsReverseOnEvenRounds(): void
    {
        [$a, $b] = new TargetProvider()->only(['css']);
        $old = $this->side('v3.6.0');
        $new = $this->side('3.x');

        $first = RoundPlan::order([$a, $b], [$old, $new], 1);
        $second = RoundPlan::order([$a, $b], [$old, $new], 2);

        self::assertSame([[$a, $old], [$a, $new], [$b, $old], [$b, $new]], $first);
        self::assertSame([[$b, $new], [$b, $old], [$a, $new], [$a, $old]], $second);
    }

    #[DataProvider('provideThreeSidesRounds')]
    public function testThreeSidesRotateAndWrapAround(int $round, array $expectedIndexes): void
    {
        $targets = new TargetProvider()->only(['css']);
        $sides = [$this->side('a'), $this->side('b'), $this->side('c')];
        $expected = array_map(static fn (array $indexes): array => [$targets[$indexes[0]], $sides[$indexes[1]]], $expectedIndexes);

        $plan = RoundPlan::order($targets, $sides, $round);

        self::assertSame($expected, $plan);
    }

    /**
     * @return iterable<string, array{int, list<array{int, int}>}>
     */
    public static function provideThreeSidesRounds(): iterable
    {
        yield 'round 1' => [1, [[0, 0], [0, 1], [0, 2], [1, 0], [1, 1], [1, 2]]];
        yield 'round 2 reverses the targets' => [2, [[1, 1], [1, 2], [1, 0], [0, 1], [0, 2], [0, 0]]];
        yield 'round 3' => [3, [[0, 2], [0, 0], [0, 1], [1, 2], [1, 0], [1, 1]]];
        yield 'round 4 wraps to the first side' => [4, [[1, 0], [1, 1], [1, 2], [0, 0], [0, 1], [0, 2]]];
    }

    private function side(string $label): Side
    {
        return new Side($label, '/tmp', '/tmp', 8180, 'abc1234', false, VendorVersions::of('8.2.0', '3.30.0', '1.5.0'), false);
    }
}
