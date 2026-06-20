<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Live;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Live\LiveEditor;

final class LiveEditorTraitTest extends TestCase
{
    public function testSaveDraftMarksFieldCleanAndStampsTime(): void
    {
        $host = $this->host();
        self::assertTrue($host->isDirty('body'));
        $host->saveDraft('body', 'hello');
        self::assertFalse($host->isDirty('body'));
        self::assertSame('hello', $host->draftRepo->store['42']['body']);
        self::assertNotNull($host->getLastSavedAt('body'));
    }

    public function testMarkDirtyExplicit(): void
    {
        $host = $this->host();
        $host->saveDraft('body', 'x');
        self::assertFalse($host->isDirty('body'));
        $host->markDirty('body');
        self::assertTrue($host->isDirty('body'));
    }

    private function host(): object
    {
        return new class {
            use LiveEditor;

            public function __construct()
            {
                $this->draftRepo = new InMemoryDrafts();
            }

            public function getEntityId(): string
            {
                return '42';
            }
        };
    }
}

final class InMemoryDrafts
{
    public array $store = [];

    public function upsert(string $entityId, string $field, mixed $content): void
    {
        $this->store[$entityId][$field] = $content;
    }
}
