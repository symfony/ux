<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Native\Configuration\Rule;

final class RuleTest extends TestCase
{
    public function testToArrayWithAllValues()
    {
        $rule = new Rule(
            patterns: ['/articles/.*', '/pages/.*'],
            properties: ['context' => 'default', 'pull_to_refresh_enabled' => true],
        );

        self::assertSame([
            'patterns' => ['/articles/.*', '/pages/.*'],
            'properties' => ['context' => 'default', 'pull_to_refresh_enabled' => true],
        ], $rule->toArray());
    }

    public function testToArrayWithPatternsOnly()
    {
        $rule = new Rule(
            patterns: ['.*'],
        );

        self::assertSame([
            'patterns' => ['.*'],
        ], $rule->toArray());
    }

    public function testToArrayWithPropertiesOnly()
    {
        $rule = new Rule(
            properties: ['context' => 'modal'],
        );

        self::assertSame([
            'properties' => ['context' => 'modal'],
        ], $rule->toArray());
    }

    public function testToArrayWithNoValues()
    {
        $rule = new Rule();

        self::assertSame([], $rule->toArray());
    }
}
