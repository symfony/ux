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
 * IconSet data model derived from the Iconify IconSet Type.
 *
 * @see https://github.com/iconify/icon-sets/blob/master/collections.json
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class IconSet
{
    public const CATEGORY_GENERAL = 'General';
    public const CATEGORY_ANIMATED_ICONS = 'Animated Icons';
    public const CATEGORY_EMOJI = 'Emoji';
    public const CATEGORY_BRANDS_SOCIAL = 'Brands / Social';
    public const CATEGORY_MAPS_FLAGS = 'Maps / Flags';
    public const CATEGORY_THEMATIC = 'Thematic';
    public const CATEGORY_ARCHIVE_UNMAINTAINED = 'Archive / Unmaintained';
    public const CATEGORY_UNCATEGORIZED = 'Uncategorized';

    public function __construct(
        private string $identifier,
        private string $name,
        private array $author,
        private array $license,
        private ?int $total = null,
        private ?string $version = null,
        private ?array $samples = [],
        private array|int|null $height = [],
        private ?int $displayHeight = null,
        private ?string $category = null,
        private ?array $tags = [],
        private ?bool $palette = null,
        private ?array $suffixes = [],
        private ?array $categories = [],
        private ?bool $isFavorite = false,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPrefix(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthor(): array
    {
        return $this->author;
    }

    public function getLicense(): array
    {
        return $this->license;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getSamples(): array
    {
        return $this->samples;
    }

    public function getHeight(): array|int|null
    {
        return $this->height;
    }

    public function getDisplayHeight(): ?int
    {
        return $this->displayHeight;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getPalette(): ?bool
    {
        return $this->palette;
    }

    public function getSuffixes(): ?array
    {
        return $this->suffixes;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function getIndex(): int
    {
        return abs(crc32($this->identifier)) % 100;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite ?? false;
    }

    public function getGithub(): ?array
    {
        $urls = [
            $this->author['url'] ?? '',
            $this->licence['url'] ?? '',
        ];
        foreach ($urls as $url) {
            if (preg_match('#https://github.com/(?<owner>[^/]+)/(?<repo>[^/]+)#', $url, $matches)) {
                return [
                    'owner' => $owner = $matches['owner'],
                    'repo' => $repo = $matches['repo'],
                    'name' => $name = sprintf('%s/%s', $owner, $repo),
                    'url' => 'https://github.com/'.$name,
                ];
            }
        }

        return null;
    }

    public function isGeneral(): bool
    {
        return self::CATEGORY_GENERAL === $this->category;
    }

    public function isAnimatedIcons(): bool
    {
        return self::CATEGORY_ANIMATED_ICONS === $this->category;
    }

    public function isEmoji(): bool
    {
        return self::CATEGORY_EMOJI === $this->category;
    }

    public function isBrandsSocial(): bool
    {
        return self::CATEGORY_BRANDS_SOCIAL === $this->category;
    }

    public function isMapsFlags(): bool
    {
        return self::CATEGORY_MAPS_FLAGS === $this->category;
    }

    public function isThematic(): bool
    {
        return self::CATEGORY_THEMATIC === $this->category;
    }

    public function isArchiveUnmaintained(): bool
    {
        return self::CATEGORY_ARCHIVE_UNMAINTAINED === $this->category;
    }
}
