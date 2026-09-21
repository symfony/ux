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
     * @param array<string, int|float|string|bool|null> $data
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
                \sprintf(
                    'The event "%s" data "%s" expect to be "%s", but "%s" given.',
                    $this->eventName,
                    $key,
                    self::export($value),
                    self::export($this->data[$key])
                )
            );
        }

        return $this;
    }

    private static function export(mixed $value): string
    {
        if (\is_scalar($value) || null === $value) {
            return (string) $value;
        }

        if (\is_object($value) && !$value instanceof \Stringable) {
            return \sprintf('object(%s)', $value::class);
        }

        return json_encode($value, \JSON_UNESCAPED_SLASHES) ?: var_export($value, true);
    }

    public function withData(array $expectedEventData): void
    {
        $this->testCase::assertEquals($expectedEventData, $this->data, \sprintf('The event "%s" data is different than expected.', $this->eventName));
    }
}
