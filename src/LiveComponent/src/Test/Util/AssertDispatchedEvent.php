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

final class AssertDispatchedEvent
{
    /**
     * @param array<string, int|float|string|bool|null> $payload
     */
    public function __construct(
        private readonly TestCase $testCase,
        private readonly string $eventName,
        private readonly array $payload,
    ) {
    }

    /**
     * @return self
     */
    public function withPayloadSubset(array $expectedEventPayload): object
    {
        foreach ($expectedEventPayload as $key => $value) {
            $this->testCase::assertArrayHasKey($key, $this->payload, \sprintf('The expected event "%s" data "%s" does not exists', $this->eventName, $key));
            $this->testCase::assertSame(
                $value,
                $this->payload[$key],
                \sprintf(
                    'The event "%s" data "%s" expect to be "%s", but "%s" given.',
                    $this->eventName,
                    $key,
                    $value,
                    $this->payload[$key]
                )
            );
        }

        return $this;
    }

    public function withPayload(array $expectedEventPayload): void
    {
        $this->testCase::assertEquals(
            $expectedEventPayload,
            $this->payload,
            \sprintf('The event "%s" payload is different than expected.', $this->eventName)
        );
    }
}
