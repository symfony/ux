<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Policy;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class UploadPolicy
{
    /**
     * @param list<string> $allowedTypes
     */
    public function __construct(
        public string $uploader,
        public int $maxSize,
        public array $allowedTypes,
        public int $maxFiles,
        public int $expiresAt,
        public ?string $fieldName = null,
    ) {
    }

    public function allows(string $mimeType): bool
    {
        foreach ($this->allowedTypes as $allowed) {
            if ($allowed === $mimeType || (str_ends_with($allowed, '/*') && str_starts_with($mimeType, substr($allowed, 0, -1)))) {
                return true;
            }
        }

        return [] === $this->allowedTypes;
    }

    public function getUploaderName(): string
    {
        return $this->uploader;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @return list<string>
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }
}
