<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Block;

use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Exception\ContentSchemaException;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

abstract class AbstractBlockTransformer implements EditorContentTransformerInterface
{
    public function getContentClass(): string
    {
        return BlockContent::class;
    }

    public function getStorageShape(): StorageShape
    {
        return StorageShape::Json;
    }

    public function transform(?EditorContentInterface $content): ?array
    {
        if (null === $content) {
            return null;
        }
        if (!$content instanceof BlockContent) {
            throw new \InvalidArgumentException(sprintf('Expected BlockContent, got %s', get_debug_type($content)));
        }

        return ['version' => $content->schemaVersion, 'blocks' => $content->blocks];
    }

    public function reverseTransform(mixed $stored): ?BlockContent
    {
        if (null === $stored || [] === $stored) {
            return null;
        }
        if (!\is_array($stored)) {
            throw new ContentSchemaException(sprintf('Expected array, got %s', get_debug_type($stored)));
        }
        $blocks = $stored['blocks'] ?? [];
        if (!\is_array($blocks)) {
            throw new ContentSchemaException('"blocks" must be an array');
        }

        return new BlockContent(
            blocks: $blocks,
            schemaVersion: (string) ($stored['version'] ?? '1.0'),
            metadata: ['bridgeId' => $this->getBridgeId()],
        );
    }

    abstract public function getBridgeId(): string;
}
