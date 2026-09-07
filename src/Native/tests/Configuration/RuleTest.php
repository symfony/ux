<?php

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
    public function testToArrayWithAllValues(): void
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

    public function testToArrayWithPatternsOnly(): void
    {
        $rule = new Rule(
            patterns: ['.*'],
        );

        self::assertSame([
            'patterns' => ['.*'],
        ], $rule->toArray());
    }

    public function testToArrayWithPropertiesOnly(): void
    {
        $rule = new Rule(
            properties: ['context' => 'modal'],
        );

        self::assertSame([
            'properties' => ['context' => 'modal'],
        ], $rule->toArray());
    }

    public function testToArrayWithNoValues(): void
    {
        $rule = new Rule();

        self::assertSame([], $rule->toArray());
    }
}
