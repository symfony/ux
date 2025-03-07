<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\ComponentRepository;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final readonly class RepositoryIdentity
{
    public function __construct(
        private int $type,
        private string $vendor,
        private ?string $package = null,
        private ?string $version = 'main',
    ) {
        if (!\in_array($type, [RepositorySources::EMBEDDED, RepositorySources::GITHUB], true)) {
            throw new \InvalidArgumentException('Only "official" and "github" types are supported for the moment.');
        }
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
