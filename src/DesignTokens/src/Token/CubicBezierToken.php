<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Token;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Token\Css\CssValue;

/**
 * @extends AbstractToken<list<float>>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CubicBezierToken extends AbstractToken
{
    /**
     * @param array<string, mixed> $extensions
     *
     * @throws InvalidArgumentException when the value is not four numbers
     */
    public function __construct(
        mixed $value,
        ?string $description = null,
        array $extensions = [],
        bool|string|null $deprecated = null,
    ) {
        if (!\is_array($value) || !array_is_list($value) || 4 !== \count($value)) {
            throw new InvalidArgumentException('A cubicBezier token value must be a list of four numbers.');
        }
        foreach ($value as $number) {
            if (!\is_int($number) && (!\is_float($number) || !is_finite($number))) {
                throw new InvalidArgumentException('A cubicBezier token value must be a list of four numbers.');
            }
        }

        parent::__construct($value, $description, $extensions, $deprecated);
    }

    public function getType(): string
    {
        return 'cubicBezier';
    }

    public function __toString(): string
    {
        return \sprintf('cubic-bezier(%s)', implode(', ', array_map(CssValue::number(...), $this->value)));
    }
}
