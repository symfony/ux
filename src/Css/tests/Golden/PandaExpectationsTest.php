<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

use PHPUnit\Framework\TestCase;

final class PandaExpectationsTest extends TestCase
{
    public function testDumpIsSortedAndLoadsBack(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'ux-css-expectations');
        new PandaExpectations(['b', 'a'], ['z' => 'later', 'y' => "it's out of v1"])->dump($file);

        $expectations = PandaExpectations::load($file);
        unlink($file);

        $this->assertSame(['a', 'b'], $expectations->passing);
        $this->assertSame(['y' => "it's out of v1", 'z' => 'later'], $expectations->skipped);
        $this->assertTrue($expectations->mustPass('a'));
        $this->assertFalse($expectations->mustPass('y'));
        $this->assertSame('later', $expectations->skipReason('z'));
        $this->assertNull($expectations->skipReason('a'));
    }

    public function testDumpingTheCommittedExpectationsLeavesThemUnchanged(): void
    {
        $committed = __DIR__.'/../Fixtures/Panda/expectations.php';
        $file = tempnam(sys_get_temp_dir(), 'ux-css-expectations');
        PandaExpectations::load($committed)->dump($file);

        $this->assertFileEquals($committed, $file);
        unlink($file);
    }
}
