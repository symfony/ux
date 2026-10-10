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

/**
 * @template TValue
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
abstract class AbstractToken implements TokenInterface
{
    /**
     * @var TValue
     */
    protected readonly mixed $value;

    /** @var array<string, mixed> */
    protected readonly array $extensions;

    protected readonly ?string $description;

    protected readonly bool|string|null $deprecated;

    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        mixed $value,
        ?string $description = null,
        array $extensions = [],
        bool|string|null $deprecated = null,
    ) {
        $this->value = $value;
        $this->description = $description;
        $this->extensions = $extensions;
        $this->deprecated = $deprecated;
    }

    /**
     * @return TValue
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isDeprecated(): bool
    {
        return false !== $this->deprecated && null !== $this->deprecated;
    }

    public function getDeprecationMessage(): ?string
    {
        return \is_string($this->deprecated) && '' !== $this->deprecated ? $this->deprecated : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    protected static function member(string $type, mixed $value, string $fallback = ''): string
    {
        return TokenFactory::project($type, $value, $fallback);
    }
}
