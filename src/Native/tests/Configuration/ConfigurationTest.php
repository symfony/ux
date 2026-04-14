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
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

final class ConfigurationTest extends TestCase
{
    public function testToArrayWithAllValues()
    {
        $configuration = new Configuration(
            settings: ['use_local_db' => true, 'cable' => ['script_url' => 'https://example.com/cable.js']],
            rules: [
                new Rule(['.*'], ['context' => 'default', 'pull_to_refresh_enabled' => true]),
            ],
        );

        self::assertSame([
            'settings' => ['use_local_db' => true, 'cable' => ['script_url' => 'https://example.com/cable.js']],
            'rules' => [
                [
                    'patterns' => ['.*'],
                    'properties' => ['context' => 'default', 'pull_to_refresh_enabled' => true],
                ],
            ],
        ], $configuration->toArray());
    }

    public function testToArrayWithSettingsOnly()
    {
        $configuration = new Configuration(
            settings: ['use_local_db' => false],
        );

        self::assertSame([
            'settings' => ['use_local_db' => false],
        ], $configuration->toArray());
    }

    public function testToArrayWithRulesOnly()
    {
        $configuration = new Configuration(
            rules: [
                new Rule(['.*'], ['context' => 'default']),
            ],
        );

        self::assertSame([
            'rules' => [
                [
                    'patterns' => ['.*'],
                    'properties' => ['context' => 'default'],
                ],
            ],
        ], $configuration->toArray());
    }

    public function testToArrayWithNoValues()
    {
        $configuration = new Configuration();

        self::assertSame([], $configuration->toArray());
    }

    public function testToArrayWithMultipleRules()
    {
        $configuration = new Configuration(
            rules: [
                new Rule(['/articles/.*'], ['context' => 'default']),
                new Rule(['/admin/.*'], ['context' => 'modal']),
                new Rule(properties: ['pull_to_refresh_enabled' => false]),
            ],
        );

        self::assertSame([
            'rules' => [
                [
                    'patterns' => ['/articles/.*'],
                    'properties' => ['context' => 'default'],
                ],
                [
                    'patterns' => ['/admin/.*'],
                    'properties' => ['context' => 'modal'],
                ],
                [
                    'properties' => ['pull_to_refresh_enabled' => false],
                ],
            ],
        ], $configuration->toArray());
    }
}
