<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Generator;

use Symfony\UX\DesignTokens\Token\TokenInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class DtcgGenerator implements GeneratorInterface
{
    public function generate(array $resolvedTokens, array $context = []): string
    {
        return json_encode(
            $this->group($resolvedTokens),
            \JSON_PRETTY_PRINT | \JSON_PRESERVE_ZERO_FRACTION | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        )."\n";
    }

    /** @param array<array-key, mixed> $tokens */
    private function group(array $tokens): \stdClass
    {
        $group = new \stdClass();

        foreach ($tokens as $name => $value) {
            if ('$description' === $name && \is_string($value)) {
                $group->{'$description'} = $value;
            } elseif ('$extensions' === $name && \is_array($value)) {
                $group->{'$extensions'} = $this->object($value);
            } elseif ($value instanceof TokenInterface) {
                $group->{(string) $name} = $this->token($value);
            } elseif (\is_array($value)) {
                $group->{(string) $name} = $this->group($value);
            }
        }

        return $group;
    }

    private function token(TokenInterface $token): \stdClass
    {
        $node = new \stdClass();
        $node->{'$type'} = $token->getType();
        $node->{'$value'} = $this->nativeValue($token->getValue());

        if (null !== $token->getDescription()) {
            $node->{'$description'} = $token->getDescription();
        }
        if ([] !== $token->getExtensions()) {
            $node->{'$extensions'} = $this->object($token->getExtensions());
        }
        if ($token->isDeprecated()) {
            $node->{'$deprecated'} = $token->getDeprecationMessage() ?? true;
        }

        return $node;
    }

    private function nativeValue(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->nativeValue(...), $value);
        }

        return $this->object($value);
    }

    /** @param array<array-key, mixed> $values */
    private function object(array $values): \stdClass
    {
        $object = new \stdClass();

        foreach ($values as $name => $value) {
            $object->{(string) $name} = $this->nativeValue($value);
        }

        return $object;
    }
}
