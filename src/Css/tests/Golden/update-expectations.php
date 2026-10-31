<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\UX\Css\Exception\UnsupportedStyleException;
use Symfony\UX\Css\Tests\Golden\OutOfScopeException;
use Symfony\UX\Css\Tests\Golden\PandaCaseLoader;
use Symfony\UX\Css\Tests\Golden\PandaCaseRunner;
use Symfony\UX\Css\Tests\Golden\PandaExpectations;

require __DIR__.'/../../vendor/autoload.php';

$fixturesDir = __DIR__.'/../Fixtures/Panda';
$loader = new PandaCaseLoader($fixturesDir);
$runner = new PandaCaseRunner($loader);
$expectations = PandaExpectations::load($fixturesDir.'/expectations.php');

$passing = [];
$regressions = [];
foreach ($loader->cases() as $id => $case) {
    if (null !== $case->unsupported || null !== $expectations->skipReason($id)) {
        continue;
    }

    try {
        $passes = $case->matches($runner->run($case));
    } catch (UnsupportedStyleException|OutOfScopeException) {
        $passes = false;
    }

    if ($passes) {
        $passing[] = $id;
    } elseif ($expectations->mustPass($id)) {
        $regressions[] = $id;
    }
}

if ($regressions && !in_array('--allow-regressions', $argv, true)) {
    fwrite(\STDERR, "These cases passed before and fail now:\n  ".implode("\n  ", $regressions)."\n");
    fwrite(\STDERR, "Fix them, or rerun with --allow-regressions to drop them from the ratchet.\n");

    exit(1);
}

new PandaExpectations($passing, $expectations->skipped)->dump($fixturesDir.'/expectations.php');

printf("%d cases pass, %d of them new.\n", count($passing), count(array_diff($passing, $expectations->passing)));
