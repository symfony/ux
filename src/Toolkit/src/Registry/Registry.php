<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

/**
 * @internal
 */
final class Registry
{
    /**
     * @var RegistryItem[]
     */
    private array $items = [];

    /**
     * @var string[]
     */
    private array $licenses = [];

    /**
     * @var array array{name: string, email: string|null}[]
     */
    private array $authors = [];

    /**
     * @var string|null homepage
     */
    private ?string $homepage;

    /**
     * @var string|null name
     */
    private ?string $name;

    public static function empty(): self
    {
        return new self();
    }

    public function add(RegistryItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return RegistryItem[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function has(string $name, RegistryItemType $type = RegistryItemType::Component): bool
    {
        return null !== $this->get($name, $type);
    }

    public function get(string $name, RegistryItemType $type = RegistryItemType::Component): ?RegistryItem
    {
        foreach ($this->items as $item) {

            if ($item->type !== $type) {
                continue;
            }

            if ($item->name === $name) {
                return $item;
            }
        }

        return null;
    }

    public function addLicense(string $license): void
    {
        $this->licenses[] = $license;
    }

    public function addAuthor(string $name, ?string $email): void
    {
        $this->authors[] = [
            'name' => $name,
            'email' => $email,
        ];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setHomepage(string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLicenses(): array
    {
        return $this->licenses;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }
}
