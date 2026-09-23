<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Test\Util;

use PHPUnit\Framework\TestCase;

final class AssertEmittedEvent
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly TestCase $testCase,
        private readonly string $eventName,
        private readonly array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $expectedEventData
     *
     * @return self
     */
    public function withDataSubset(array $expectedEventData): object
    {
        foreach ($expectedEventData as $key => $value) {
            $this->testCase::assertArrayHasKey($key, $this->data, \sprintf('The expected event "%s" data "%s" does not exists', $this->eventName, $key));
            $this->testCase::assertSame(
                $value,
                $this->data[$key],
                \sprintf('The event "%s" data "%s" is different than expected.', $this->eventName, $key)
            );
        }

        return $this;
    }

    public function withData(array $expectedEventData): void
    {
        $this->testCase::assertEquals($expectedEventData, $this->data, \sprintf('The event "%s" data is different than expected.', $this->eventName));
    }
}
