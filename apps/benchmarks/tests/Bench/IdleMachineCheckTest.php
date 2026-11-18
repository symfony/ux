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

use App\Bench\IdleMachineCheck;
use PHPUnit\Framework\TestCase;

final class IdleMachineCheckTest extends TestCase
{
    private const PS_OUTPUT = " %CPU COMM\n 12.3 /Applications/Foo\n  4.9 /usr/bin/bar\n 50.0 /usr/bin/php\n 40.0 /usr/sbin/ab\n 30.0 /opt/frankenphp\n 7,5 /opt/baz\n";
    private const PS_IDLE_OUTPUT = " %CPU COMM\n  4.9 /usr/bin/bar\n  5.0 /usr/bin/qux\n 50.0 /usr/bin/php\n";
    private const PS_ONE_BUSY_OUTPUT = " %CPU COMM\n 12.3 /Applications/Foo\n";

    public function testItListsTheBusyProcesses(): void
    {
        $check = new IdleMachineCheck(static fn (): float => 1.0, static fn (): string => self::PS_OUTPUT, static fn (): int => 10);

        $busy = $check->busyProcesses();

        self::assertSame(['12.3% /Applications/Foo', '7.5% /opt/baz'], $busy);
    }

    public function testItIsIdleWithALowLoadAndIdleProcesses(): void
    {
        $check = new IdleMachineCheck(static fn (): float => 1.0, static fn (): string => self::PS_IDLE_OUTPUT, static fn (): int => 10);

        $busy = $check->isBusy();

        self::assertFalse($busy);
    }

    public function testItIsBusyWithAHighLoad(): void
    {
        $check = new IdleMachineCheck(static fn (): float => 5.1, static fn (): string => self::PS_IDLE_OUTPUT, static fn (): int => 10);

        $busy = $check->isBusy();

        self::assertTrue($busy);
    }

    public function testItIsBusyWithABusyProcess(): void
    {
        $check = new IdleMachineCheck(static fn (): float => 1.0, static fn (): string => self::PS_ONE_BUSY_OUTPUT, static fn (): int => 10);

        $busy = $check->isBusy();

        self::assertTrue($busy);
    }

    public function testItReportsTheLoadAndTheBusyProcesses(): void
    {
        $check = new IdleMachineCheck(static fn (): float => 5.63, static fn (): string => self::PS_ONE_BUSY_OUTPUT, static fn (): int => 10);

        $report = $check->report();

        self::assertSame([
            'Load average 5.63 over the last minute, more than half of the 10 cores',
            'Busy process: 12.3% /Applications/Foo',
        ], $report);
    }
}
