<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Live;

trait LiveEditor
{
    /**
     * @var array<string, bool>
     */
    public array $dirty = [];

    /**
     * @var array<string, \DateTimeImmutable>
     */
    public array $lastSavedAt = [];

    /** Host class assigns this in its constructor — any object with upsert(entityId, field, content) signature. */
    public mixed $draftRepo;

    public function saveDraft(string $field, mixed $content): void
    {
        $this->draftRepo->upsert($this->getEntityId(), $field, $content);
        $this->dirty[$field] = false;
        $this->lastSavedAt[$field] = new \DateTimeImmutable();
    }

    public function isDirty(string $field): bool
    {
        return $this->dirty[$field] ?? true;
    }

    public function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }

    public function getLastSavedAt(string $field): ?\DateTimeImmutable
    {
        return $this->lastSavedAt[$field] ?? null;
    }

    abstract public function getEntityId(): string;
}
