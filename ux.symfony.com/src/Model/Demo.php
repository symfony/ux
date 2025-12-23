<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class Demo
{
    private array $authors;
    public function __construct(
        private string $identifier,
        private string $name,
        private string $description,
        Person|string|array $author,
        private \DateTimeImmutable|string $publishedAt,
        private array $tags,
    ) {
        foreach ((array) $author as $person) {
            if (!is_string($person) && !$person instanceof Person) {
                throw new \InvalidArgumentException('Allowed typed for author: string or Person');
            }
            $this->authors[] = $person;
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAuthor(): Person|string
    {
        return $this->authors[0];
    }

    /**
     * @return array<Person|string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getPublishedAt(): \DateTimeImmutable|string
    {
        return $this->publishedAt;
    }
}
