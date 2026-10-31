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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class PandaGoldenTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__.'/../Fixtures/Panda';

    private static ?PandaCaseLoader $loader = null;
    private static ?PandaExpectations $expectations = null;
    private static ?PandaCaseRunner $runner = null;

    public static function provideCases(): iterable
    {
        foreach (self::loader()->cases() as $id => $case) {
            yield $id => [$case];
        }
    }

    #[DataProvider('provideCases')]
    public function testOutputIsIdenticalToPanda(PandaCase $case): void
    {
        if (null !== $case->unsupported) {
            $this->markTestSkipped('Recorded as unsupported: '.$case->unsupported);
        }
        if (null !== $reason = self::expectations()->skipReason($case->id)) {
            $this->markTestSkipped($reason);
        }

        $mustPass = self::expectations()->mustPass($case->id);

        try {
            $actual = self::runner()->run($case);
        } catch (OutOfScopeException $e) {
            if ($mustPass) {
                throw $e;
            }
            $this->markTestSkipped('Out of v1: '.$e->getMessage());
        } catch (UnsupportedStyleException $e) {
            if ($mustPass) {
                throw $e;
            }
            $this->markTestIncomplete('Not ported yet: '.$e->getMessage());
        }

        if (!$mustPass && !$case->matches($actual)) {
            $this->markTestIncomplete('Output differs from Panda, see '.$case->sourceLocation());
        }

        $this->assertSame(
            $case->normalize($case->expectedOutput()),
            $case->normalize($actual),
            'Panda source: '.$case->sourceLocation(),
        );
    }

    public function testExpectationsOnlyReferenceRecordedCases(): void
    {
        $ids = array_keys(iterator_to_array(self::loader()->cases()));
        $expectations = self::expectations();

        $this->assertSame([], array_values(array_diff($expectations->passing, $ids)));
        $this->assertSame([], array_values(array_diff(array_keys($expectations->skipped), $ids)));
    }

    private static function loader(): PandaCaseLoader
    {
        return self::$loader ??= new PandaCaseLoader(self::FIXTURES_DIR);
    }

    private static function runner(): PandaCaseRunner
    {
        return self::$runner ??= new PandaCaseRunner(self::loader());
    }

    private static function expectations(): PandaExpectations
    {
        return self::$expectations ??= PandaExpectations::load(self::FIXTURES_DIR.'/expectations.php');
    }
}
