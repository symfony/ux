<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Test-only stub exposing a "mergeable_tokens" filter that returns a lenient
 * MergeableInterface value object, so the component render pipeline can be
 * exercised without depending on a concrete implementation (e.g. tailwind_classes).
 *
 * The value object is an anonymous class built at runtime: reflecting this
 * extension never loads it, so nothing breaks when twig/html-extra is too old to
 * provide MergeableInterface (the filter is only ever called from a guarded test).
 */
final class MergeableExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mergeable_tokens', [self::class, 'mergeableTokens']),
        ];
    }

    public static function mergeableTokens(mixed $tokens): object
    {
        return new class(is_iterable($tokens) ? [...$tokens] : [$tokens]) implements \Stringable, \Twig\Extra\Html\HtmlAttr\AttributeValueInterface, \Twig\Extra\Html\HtmlAttr\MergeableInterface {
            /** @param list<string> $tokens */
            public function __construct(private array $tokens)
            {
            }

            public function __toString(): string
            {
                return $this->getValue() ?? '';
            }

            public function getValue(): ?string
            {
                return $this->tokens ? implode(' ', $this->tokens) : null;
            }

            public function mergeInto(mixed $previous): mixed
            {
                $previousTokens = $previous instanceof self ? $previous->tokens : array_filter([(string) $previous], 'strlen');

                return new self([...$previousTokens, ...$this->tokens]);
            }

            public function appendFrom(mixed $newValue): mixed
            {
                $newTokens = $newValue instanceof self ? $newValue->tokens : (is_iterable($newValue) ? [...$newValue] : array_filter([(string) $newValue], 'strlen'));

                return new self([...$this->tokens, ...$newTokens]);
            }
        };
    }
}
