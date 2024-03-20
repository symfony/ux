<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Icon;

/**
 * Icon model.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
class Icon implements \Stringable
{
    public function __construct(
        private readonly string $prefix,
        private readonly string $name,
    ) {
        if (!preg_match('/[a-z-]+/', $this->prefix)) {
            throw new \InvalidArgumentException(sprintf('Invalid icon prefix "%s".', $this->prefix));
        }
        if (!preg_match('/[a-z-0-9]+/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('Invalid icon name "%s".', $this->name));
        }
    }

    public static function fromIdentifier(string $identifier): self
    {
        [$prefix, $name] = explode(':', $identifier, 2);

        return new self(strtolower($prefix), strtolower($name));
    }

    public static function create(string $prefix, string $name): self
    {
        return new self(strtolower($prefix), strtolower($name));
    }

    public function getIdentifier(): string
    {
        return $this->prefix.':'.$this->name;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImageUrl(): string
    {
        return sprintf('https://api.iconify.design/%s/%s.svg', $this->prefix, $this->name);
    }

    //return 'https://iconify.design/icon-sets/'.$this->prefix.'/'.$this->name.'.svg';

    public function __toString(): string
    {
        return $this->getIdentifier();
    }
}
