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

use App\Bench\Measurement;
use App\Bench\Round;
use App\Bench\RoundsLog;
use PHPUnit\Framework\TestCase;

final class RoundsLogTest extends TestCase
{
    public function testItRoundTripsRounds(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rounds');
        $log = new RoundsLog($path);
        $first = new Round('before', 'css/ux-css', 1, new Measurement(76.5, 41.0, 62.0, 142.0, 0));
        $second = new Round('after', 'live/post-form', 2, new Measurement(133.39, 28.0, 34.0, 34.0, 3));

        try {
            $log->clear();
            $log->append($first);
            $log->append($second);

            $rounds = $log->rounds();
        } finally {
            unlink($path);
        }

        self::assertCount(2, $rounds);
        self::assertSame('before', $rounds[0]->mode);
        self::assertSame('css/ux-css', $rounds[0]->target);
        self::assertSame(1, $rounds[0]->round);
        self::assertSame(76.5, $rounds[0]->measurement->requestsPerSecond);
        self::assertSame(142.0, $rounds[0]->measurement->p99);
        self::assertSame('after', $rounds[1]->mode);
        self::assertSame(2, $rounds[1]->round);
        self::assertSame(133.39, $rounds[1]->measurement->requestsPerSecond);
        self::assertSame(3, $rounds[1]->measurement->errors);
    }

    public function testItReturnsNoRoundsWhenTheFileIsMissing(): void
    {
        $log = new RoundsLog(sys_get_temp_dir().'/rounds-does-not-exist');

        $rounds = $log->rounds();

        self::assertSame([], $rounds);
    }
}
