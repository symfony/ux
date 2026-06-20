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

final class LiveEditorIntegrationTest extends TestCase
{
    public function testHostUsingTraitInheritsAllMethods(): void
    {
        $repo = new class {
            public array $store = [];

            public function upsert(string $id, string $field, mixed $content): void
            {
                $this->store[$id][$field] = $content;
            }
        };
        $host = new FakeArticleEditor($repo, 'a-1');

        self::assertTrue($host->isDirty('body'));
        $host->saveDraft('body', '<p>hello</p>');
        self::assertFalse($host->isDirty('body'));
        self::assertSame('<p>hello</p>', $repo->store['a-1']['body']);

        $host->saveDraft('title', 'My title');
        self::assertFalse($host->isDirty('title'));
        self::assertFalse($host->isDirty('body'));

        $host->markDirty('body');
        self::assertTrue($host->isDirty('body'));
    }
}

final class FakeArticleEditor
{
    use LiveEditor;

    public function __construct(public mixed $repo, private string $entityId)
    {
        $this->draftRepo = $repo;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }
}
